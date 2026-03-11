<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    // Create the super-admin role with all access-control.users.* permissions
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    foreach (['index', 'store', 'show', 'update', 'destroy', 'restore', 'force-delete'] as $action) {
        $role->givePermissionTo(
            Permission::firstOrCreate(['name' => "access-control.users.{$action}", 'guard_name' => 'web'])
        );
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->token = $this->admin->createToken('test')->plainTextToken;
});

test('unauthenticated users cannot list users', function (): void {
    $this->getJson(route('api.v1.access-control.users.index'))
        ->assertUnauthorized();
});

test('authenticated users without permission cannot list users', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.access-control.users.index'))
        ->assertForbidden();
});

test('users with permission can list users', function (): void {
    $this->withToken($this->token)
        ->getJson(route('api.v1.access-control.users.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('users with permission can create a user', function (): void {
    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'newuser@example.com');
});

test('creating a user validates required fields', function (): void {
    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.users.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('users with permission can view a single user', function (): void {
    $target = User::factory()->create();

    $this->withToken($this->token)
        ->getJson(route('api.v1.access-control.users.show', $target))
        ->assertOk()
        ->assertJsonPath('data.id', $target->id);
});

test('users with permission can update a user', function (): void {
    $target = User::factory()->create();

    $this->withToken($this->token)
        ->putJson(route('api.v1.access-control.users.update', $target), [
            'name' => 'Updated Name',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

test('users with permission can soft-delete a user', function (): void {
    $target = User::factory()->create();

    $this->withToken($this->token)
        ->deleteJson(route('api.v1.access-control.users.destroy', $target))
        ->assertNoContent();

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

test('users with permission can restore a soft-deleted user', function (): void {
    $target = User::factory()->create();
    $target->delete();

    $this->withToken($this->token)
        ->patchJson(route('api.v1.access-control.users.restore', $target->id))
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null);

    $this->assertNotSoftDeleted('users', ['id' => $target->id]);
});

test('users with permission can permanently delete a user', function (): void {
    $target = User::factory()->create();
    $target->delete();

    $this->withToken($this->token)
        ->deleteJson(route('api.v1.access-control.users.force-delete', $target->id))
        ->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});
