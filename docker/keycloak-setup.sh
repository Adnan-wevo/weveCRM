#!/bin/bash
set -e

KC_URL="${1:-http://localhost:8180}"
KC_ADMIN="${2:-admin}"
KC_PASS="${3:-admin}"
KC_REALM="${4:-wevetel}"
KC_CLIENT="${5:-laravel}"
APP_URL="${6:-http://localhost:8002}"
# Internal URL used for server-to-server calls (e.g. back-channel logout).
# In Docker Compose this is the nginx service URL reachable by Keycloak.
APP_INTERNAL_URL="${7:-$APP_URL}"

echo "🔑 Getting admin token..."
TOKEN=$(curl -sf -X POST "$KC_URL/realms/master/protocol/openid-connect/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=$KC_ADMIN&password=$KC_PASS&grant_type=password&client_id=admin-cli" \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "❌ Failed to get token. Check Keycloak credentials."
  exit 1
fi
echo "✅ Token acquired"

echo "🌎 Creating realm '$KC_REALM'..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$KC_URL/admin/realms" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"realm\":\"$KC_REALM\",\"enabled\":true,\"displayName\":\"Wevetel\"}")
if [ "$STATUS" = "201" ]; then
  echo "✅ Realm created"
elif [ "$STATUS" = "409" ]; then
  echo "ℹ️  Realm already exists, skipping"
else
  echo "❌ Failed to create realm (HTTP $STATUS)"
  exit 1
fi

echo "📝 Enabling user registration & password reset on realm..."
curl -sf -o /dev/null -X PUT "$KC_URL/admin/realms/$KC_REALM" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"registrationAllowed":true,"resetPasswordAllowed":true,"rememberMe":true}'
echo "✅ Realm settings updated"

echo "📦 Creating client '$KC_CLIENT'..."
# Extract scheme+host+port to build wildcard URI for any subdomain.
# e.g. APP_URL=http://wevetel.test:8002 → SCHEME=http, HOST=wevetel.test, PORT_SUFFIX=:8002
SCHEME=$(echo "$APP_URL" | sed -n 's|^\(https\?\)://.*|\1|p')
HOST=$(echo "$APP_URL" | sed 's|https\?://||' | cut -d: -f1 | cut -d/ -f1)
PORT_PART=$(echo "$APP_URL" | sed 's|https\?://[^:/]*||' | cut -d/ -f1)
WILD_BASE="${SCHEME}://*.${HOST}${PORT_PART}"

STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$KC_URL/admin/realms/$KC_REALM/clients" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"clientId\": \"$KC_CLIENT\",
    \"name\": \"Laravel App\",
    \"enabled\": true,
    \"publicClient\": false,
    \"clientAuthenticatorType\": \"client-secret\",
    \"redirectUris\": [
      \"$APP_URL/auth/keycloak/callback\",
      \"$APP_URL/auth/keycloak/link/callback\",
      \"$APP_URL/auth/keycloak/register-fresh\",
      \"$WILD_BASE/auth/keycloak/callback\",
      \"$WILD_BASE/auth/keycloak/link/callback\",
      \"$WILD_BASE/auth/keycloak/register-fresh\"
    ],
    \"webOrigins\": [\"$APP_URL\", \"$WILD_BASE\"],
    \"standardFlowEnabled\": true,
    \"directAccessGrantsEnabled\": false,
    \"attributes\": {
      \"post.logout.redirect.uris\": \"$APP_URL/*##$WILD_BASE/*\",
      \"backchannel.logout.url\": \"$APP_INTERNAL_URL/auth/keycloak/backchannel-logout\",
      \"backchannel.logout.session.required\": \"true\"
    }
  }")
if [ "$STATUS" = "201" ]; then
  echo "✅ Client created"
elif [ "$STATUS" = "409" ]; then
  echo "ℹ️  Client already exists, continuing"
else
  echo "❌ Failed to create client (HTTP $STATUS)"
  exit 1
fi

echo "🔓 Getting client secret..."
CLIENT_ID=$(curl -sf "$KC_URL/admin/realms/$KC_REALM/clients?clientId=$KC_CLIENT" \
  -H "Authorization: Bearer $TOKEN" \
  | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

SECRET=$(curl -sf "$KC_URL/admin/realms/$KC_REALM/clients/$CLIENT_ID/client-secret" \
  -H "Authorization: Bearer $TOKEN" \
  | grep -o '"value":"[^"]*"' | cut -d'"' -f4)

if [ -z "$SECRET" ]; then
  echo "❌ Failed to get client secret"
  exit 1
fi

# ── Microsoft Identity Provider ──────────────────────────────────────────────
MS_CLIENT_ID="${MICROSOFT_CLIENT_ID:-}"
MS_CLIENT_SECRET="${MICROSOFT_CLIENT_SECRET:-}"
MS_TENANT_ID="${MICROSOFT_TENANT_ID:-common}"

if [ -n "$MS_CLIENT_ID" ] && [ -n "$MS_CLIENT_SECRET" ]; then
  echo "🔷 Adding Microsoft Identity Provider..."
  MS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$KC_URL/admin/realms/$KC_REALM/identity-provider/instances" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"alias\": \"microsoft\",
      \"displayName\": \"Microsoft\",
      \"providerId\": \"microsoft\",
      \"enabled\": true,
      \"trustEmail\": true,
      \"storeToken\": false,
      \"addReadTokenRoleOnCreate\": false,
      \"firstBrokerLoginFlowAlias\": \"first broker login\",
      \"config\": {
        \"clientId\": \"$MS_CLIENT_ID\",
        \"clientSecret\": \"$MS_CLIENT_SECRET\",
        \"tenantId\": \"$MS_TENANT_ID\",
        \"defaultScope\": \"openid profile email User.Read\"
      }
    }")
  if [ "$MS_STATUS" = "201" ]; then
    echo "✅ Microsoft Identity Provider added"
    echo "ℹ️  Add this Redirect URI to your Azure App Registration:"
    echo "   $KC_URL/realms/$KC_REALM/broker/microsoft/endpoint"
  elif [ "$MS_STATUS" = "409" ]; then
    echo "ℹ️  Microsoft Identity Provider already exists, skipping"
  else
    echo "⚠️  Failed to add Microsoft IDP (HTTP $MS_STATUS), skipping"
  fi
else
  echo "ℹ️  MICROSOFT_CLIENT_ID/SECRET not set — skipping Microsoft IDP"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Keycloak setup complete!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Realm:         $KC_REALM"
echo "Client ID:     $KC_CLIENT"
echo "Client Secret: $SECRET"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# ── Auto-update .env if mounted ───────────────────────────────────────────────
ENV_FILE="/var/www/html/.env"

# Helper: insert or replace a key=value in the .env file.
# Uses cat > file instead of sed -i because the file is a Docker bind-mount
# and sed -i fails with "Resource busy" when trying to rename over it.
set_env_var() {
    local key="$1"
    local value="$2"
    if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
        local tmpfile
        tmpfile=$(mktemp)
        sed "s|^${key}=.*|${key}=${value}|" "$ENV_FILE" > "$tmpfile"
        cat "$tmpfile" > "$ENV_FILE"
        rm -f "$tmpfile"
    else
        echo "${key}=${value}" >> "$ENV_FILE"
    fi
}

if [ -f "$ENV_FILE" ]; then
    set_env_var "KEYCLOAK_CLIENT_ID" "$KC_CLIENT"
    set_env_var "KEYCLOAK_CLIENT_SECRET" "$SECRET"
    echo "✅ .env updated with KEYCLOAK_CLIENT_ID & KEYCLOAK_CLIENT_SECRET"
else
    echo ""
    echo "⚠️  .env not mounted at $ENV_FILE — add these manually:"
    echo "KEYCLOAK_CLIENT_ID=$KC_CLIENT"
    echo "KEYCLOAK_CLIENT_SECRET=$SECRET"
fi
