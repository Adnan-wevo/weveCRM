<?php

namespace App\Socialite;

use GuzzleHttp\Client;
use SocialiteProviders\Keycloak\Provider;

/**
 * Custom Keycloak Socialite provider that fixes networking issues when the
 * application container talks to a Keycloak server that is outside the Docker
 * Compose network (e.g. on the host machine or a remote server).
 *
 * Problem 1 — Token exchange fails (localhost resolves to the container itself):
 *   The token URL uses the public base_url (e.g. https://localhost). Inside
 *   Docker, "localhost" means the container, not the host machine.
 *   Fix: use CURLOPT_RESOLVE to redirect the public hostname to the internal IP
 *   (derived from KEYCLOAK_INTERNAL_BASE_URL), keeping the original hostname in
 *   the URL so that SNI, Host header and SSL certificate all match perfectly.
 *
 * Problem 2 — Userinfo returns 401 Unauthorized (issuer mismatch):
 *   Keycloak issues tokens with iss = public URL. If the app calls userinfo on a
 *   different hostname, Keycloak rejects the token.
 *   Fix: decode the JWT payload locally instead of calling the userinfo endpoint.
 */
class KeycloakProvider extends Provider
{
    /**
     * Register internal_base_url as a recognised config key so getConfig() can
     * read it from services.keycloak.internal_base_url.
     *
     * @return array<int, string>
     */
    public static function additionalConfigKeys(): array
    {
        return ['base_url', 'realms', 'internal_base_url'];
    }

    /**
     * Return the token endpoint using the PUBLIC base URL so that the hostname in
     * the request matches SNI / Host / SSL certificate. The actual connection is
     * redirected to the internal IP via CURLOPT_RESOLVE in getHttpClient().
     */
    protected function getTokenUrl(): string
    {
        $base = rtrim((string) $this->getConfig('base_url'), '/');

        return $base.'/realms/'.$this->getConfig('realms', 'master').'/protocol/openid-connect/token';
    }

    /**
     * Decode the Keycloak access token (a signed JWT) and return its claims
     * instead of calling the userinfo HTTP endpoint. Keycloak always includes
     * sub, preferred_username, name, and email in the access token payload.
     *
     * @param  string  $token
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \RuntimeException('Keycloak access token is not a valid JWT.');
        }

        $payload = json_decode(
            base64_decode(strtr($parts[1], '-_', '+/')),
            true,
        );

        if (! is_array($payload)) {
            throw new \RuntimeException('Failed to decode Keycloak JWT payload.');
        }

        return $payload;
    }

    /**
     * Get a Guzzle HTTP client configured for server-to-server calls to Keycloak.
     *
     * When KEYCLOAK_INTERNAL_BASE_URL differs from KEYCLOAK_BASE_URL, curl's
     * CURLOPT_RESOLVE is used to map the public hostname to the internal IP.
     * This keeps the URL, SNI and Host header using the public hostname (so the
     * reverse proxy and SSL certificate work correctly) while connecting to the
     * right IP address.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient(): Client
    {
        $public = rtrim((string) $this->getConfig('base_url'), '/');
        $internal = rtrim((string) ($this->getConfig('internal_base_url') ?: $public), '/');

        // No internal override — use default client.
        if ($internal === $public) {
            return new Client;
        }

        $publicHost = parse_url($public, PHP_URL_HOST) ?: 'localhost';
        $publicPort = parse_url($public, PHP_URL_PORT) ?: (parse_url($public, PHP_URL_SCHEME) === 'https' ? 443 : 80);

        // Resolve the internal URL to an IP address.
        $internalHost = parse_url($internal, PHP_URL_HOST) ?: $publicHost;
        $internalIp = gethostbyname($internalHost);

        // CURLOPT_RESOLVE entry: "publichost:port:internal_ip"
        $resolveEntry = "{$publicHost}:{$publicPort}:{$internalIp}";

        return new Client([
            'verify' => false,
            'curl' => [
                CURLOPT_RESOLVE => [$resolveEntry],
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ],
        ]);
    }
}
