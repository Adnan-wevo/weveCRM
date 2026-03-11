<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Maize\MagicLogin\Facades\MagicLink;

// Magic login request page ─────────────────────────────────────────────

test('magic login request page can be rendered', function () {
    $this->get(route('magic-login.request'))->assertOk();
});

test('magic login request page redirects authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('magic-login.request'))
        ->assertRedirect();
});

// Sending the magic link ───────────────────────────────────────────────

test('sends magic link to existing user', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(\App\Livewire\Auth\MagicLogin::class)
        ->set('email', $user->email)
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('linkSent', true);

    Notification::assertSentTo($user, \App\Notifications\MagicLinkNotification::class);
});

test('does not reveal whether user exists — always shows success', function () {
    Notification::fake();

    Livewire::test(\App\Livewire\Auth\MagicLogin::class)
        ->set('email', 'nobody@example.com')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('linkSent', true);

    Notification::assertNothingSent();
});

test('email is required to send magic link', function () {
    Livewire::test(\App\Livewire\Auth\MagicLogin::class)
        ->set('email', '')
        ->call('send')
        ->assertHasErrors(['email' => 'required']);
});

test('email must be valid to send magic link', function () {
    Livewire::test(\App\Livewire\Auth\MagicLogin::class)
        ->set('email', 'not-an-email')
        ->call('send')
        ->assertHasErrors(['email' => 'email']);
});

// Magic link authentication ────────────────────────────────────────────

test('magic link logs in the user and redirects to dashboard', function () {
    $user = User::factory()->create();

    // Pass an explicit mutable Carbon to avoid CarbonImmutable type mismatch in the
    // package when running on Laravel 12 (now() returns CarbonImmutable).
    $link = MagicLink::make(
        authenticatable: $user,
        expiration: Carbon::now()->addMinutes(30),
    );

    $path = parse_url($link, PHP_URL_PATH).'?'.parse_url($link, PHP_URL_QUERY);

    $this->get($path)
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('magic link cannot be used a second time', function () {
    $user = User::factory()->create();

    $link = MagicLink::make(
        authenticatable: $user,
        expiration: Carbon::now()->addMinutes(30),
        loginsLimit: 1,
    );
    $path = parse_url($link, PHP_URL_PATH).'?'.parse_url($link, PHP_URL_QUERY);

    // First use succeeds.
    $this->get($path)->assertRedirect(route('dashboard'));

    // Second use fails (signature is still valid but logins_limit is exhausted).
    $this->get($path)->assertStatus(403);
});
