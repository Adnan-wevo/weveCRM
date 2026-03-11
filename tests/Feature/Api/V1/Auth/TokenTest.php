<?php

use App\Models\User;

test('unauthenticated users can obtain a token with valid credentials', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $response = $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $user->email,
        'password' => 'secret',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

test('login fails with invalid credentials', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(401)->assertJson(['message' => 'Invalid credentials.']);
});

test('login fails with validation errors', function (): void {
    $this->postJson(route('api.v1.auth.token.store'), [
        'email' => 'not-an-email',
        'password' => '',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

test('authenticated users can revoke their token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->deleteJson(route('api.v1.auth.token.destroy'))
        ->assertNoContent();
});

test('token revocation requires authentication', function (): void {
    $this->deleteJson(route('api.v1.auth.token.destroy'))
        ->assertUnauthorized();
});
