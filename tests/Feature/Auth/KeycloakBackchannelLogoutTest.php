<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

// Helpers ──────────────────────────────────────────────────────────────

/**
 * Build a minimal JWT logout token with the given payload.
 * Signature is intentionally fake — the endpoint only decodes, not verifies.
 */
function makeLogoutToken(array $payload): string
{
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims = base64_encode(json_encode($payload));

    return "{$header}.{$claims}.fakesignature";
}

// Back-channel logout endpoint ─────────────────────────────────────────

test('backchannel logout endpoint rejects missing logout_token', function () {
    $response = $this->post(route('keycloak.backchannel-logout'));

    $response->assertStatus(400);
});

test('backchannel logout endpoint rejects malformed logout_token', function () {
    $response = $this->post(route('keycloak.backchannel-logout'), [
        'logout_token' => 'not.valid',
    ]);

    $response->assertStatus(400);
});

test('backchannel logout endpoint sets cache flag when sub is present', function () {
    $keycloakId = 'kc-user-sub-'.uniqid();

    $response = $this->post(route('keycloak.backchannel-logout'), [
        'logout_token' => makeLogoutToken(['sub' => $keycloakId, 'events' => []]),
    ]);

    $response->assertStatus(200);
    expect(Cache::has("keycloak_backchannel_logout:{$keycloakId}"))->toBeTrue();
});

test('backchannel logout endpoint rejects token without sub claim', function () {
    $response = $this->post(route('keycloak.backchannel-logout'), [
        'logout_token' => makeLogoutToken(['events' => []]),
    ]);

    $response->assertStatus(400);
});

// Back-channel logout middleware ───────────────────────────────────────

test('authenticated user is logged out on next request when backchannel flag is set', function () {
    $user = User::factory()->create(['keycloak_id' => 'kc-'.uniqid()]);

    Cache::put("keycloak_backchannel_logout:{$user->keycloak_id}", true, now()->addMinutes(5));

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
    expect(Cache::has("keycloak_backchannel_logout:{$user->keycloak_id}"))->toBeFalse();
});

test('authenticated user without keycloak_id is unaffected by backchannel logout check', function () {
    $user = User::factory()->create(['keycloak_id' => null]);

    $this->actingAs($user)->get(route('dashboard'));

    $this->assertAuthenticated();
});
