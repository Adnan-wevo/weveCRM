<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Stancl\Tenancy\Database\Models\Domain;

class KeycloakSyncUris extends Command
{
    protected $signature = 'keycloak:sync-uris
                            {--dry-run : Print the URIs that would be registered without making changes}';

    protected $description = 'Sync all registered tenant domains to Keycloak\'s client redirect URIs.';

    public function handle(): int
    {
        // Use internal URL (container-to-container) for admin API calls when available,
        // falling back to the browser-facing base URL. This allows the command to run
        // correctly both inside Docker (where localhost:8180 doesn't resolve) and on the host.
        $baseUrl = rtrim((string) (config('services.keycloak.internal_base_url') ?: config('services.keycloak.base_url')), '/');
        $realm = config('services.keycloak.realms', 'wevetel');
        $clientId = config('services.keycloak.client_id');
        $adminUser = config('services.keycloak.admin_user', 'admin');
        $adminPass = config('services.keycloak.admin_password', 'admin');
        $port = parse_url(config('app.url'), PHP_URL_PORT);
        $portSuffix = $port ? ":$port" : '';

        // ── Get admin token ────────────────────────────────────────────────────
        $tokenResponse = Http::asForm()->post(
            "{$baseUrl}/realms/master/protocol/openid-connect/token",
            [
                'username' => $adminUser,
                'password' => $adminPass,
                'grant_type' => 'password',
                'client_id' => 'admin-cli',
            ]
        );

        if (! $tokenResponse->successful()) {
            $this->error('Failed to get Keycloak admin token. Check KEYCLOAK_ADMIN_USER / KEYCLOAK_ADMIN_PASSWORD.');

            return self::FAILURE;
        }

        $token = $tokenResponse->json('access_token');

        // ── Find the internal client UUID ──────────────────────────────────────
        $clients = Http::withToken($token)
            ->get("{$baseUrl}/admin/realms/{$realm}/clients", ['clientId' => $clientId])
            ->json();

        if (empty($clients)) {
            $this->error("Client '{$clientId}' not found in realm '{$realm}'.");

            return self::FAILURE;
        }

        $uuid = $clients[0]['id'];

        // ── Build redirect URI list ────────────────────────────────────────────
        // Always include the central domain (APP_URL).
        $centralBase = rtrim(config('app.url'), '/');
        $redirectUris = [
            $centralBase.'/auth/keycloak/callback',
            $centralBase.'/auth/keycloak/link/callback',
            $centralBase.'/auth/keycloak/register-fresh',
        ];

        $postLogoutUris = [$centralBase.'/*'];
        $webOrigins = [$centralBase];

        // Add every tenant domain registered in the domains table.
        Domain::query()->orderBy('domain')->each(function (Domain $domain) use (
            $portSuffix, &$redirectUris, &$postLogoutUris, &$webOrigins
        ) {
            $scheme = str_starts_with(config('app.url'), 'https') ? 'https' : 'http';
            $base = "{$scheme}://{$domain->domain}{$portSuffix}";
            $redirectUris[] = $base.'/auth/keycloak/callback';
            $redirectUris[] = $base.'/auth/keycloak/link/callback';
            $redirectUris[] = $base.'/auth/keycloak/register-fresh';
            $postLogoutUris[] = $base.'/*';
            $webOrigins[] = $base;
        });

        $redirectUris = array_values(array_unique($redirectUris));
        $postLogoutUris = array_values(array_unique($postLogoutUris));
        $webOrigins = array_values(array_unique($webOrigins));

        $this->info('Redirect URIs to register:');
        foreach ($redirectUris as $uri) {
            $this->line("  {$uri}");
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode — no changes made.');

            return self::SUCCESS;
        }

        // ── Update the client ──────────────────────────────────────────────────
        $response = Http::withToken($token)
            ->put("{$baseUrl}/admin/realms/{$realm}/clients/{$uuid}", [
                'redirectUris' => $redirectUris,
                'webOrigins' => $webOrigins,
                'attributes' => [
                    'post.logout.redirect.uris' => implode('##', $postLogoutUris),
                ],
            ]);

        if ($response->successful()) {
            $this->info('✅ Keycloak client redirect URIs updated successfully.');

            return self::SUCCESS;
        }

        $this->error('Failed to update Keycloak client: '.$response->body());

        return self::FAILURE;
    }
}
