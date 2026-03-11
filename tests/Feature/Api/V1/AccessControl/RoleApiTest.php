<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    foreach (['index', 'store', 'show', 'update', 'destroy', 'restore', 'force-delete'] as $action) {
        $role->givePermissionTo(
            Permission::firstOrCreate(['name' => "access-control.roles.{$action}", 'guard_name' => 'web'])
        );
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->token = $this->admin->createToken('test')->plainTextToken;
});

test('unauthenticated users cannot list roles', function (): void {
    $this->getJson(route('api.v1.access-control.roles.index'))
        ->assertUnauthorized();
});

test('authenticated users without permission cannot list roles', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.access-control.roles.index'))
        ->assertForbidden();
});

test('users with permission can list roles', function (): void {
    $this->withToken($this->token)
        ->getJson(route('api.v1.access-control.roles.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('users with permission can create a role', function (): void {
    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.roles.store'), [
            'name' => 'editor',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'editor');
});

test('creating a role fails when name is missing', function (): void {
    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.roles.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('creating a role fails when name already exists', function (): void {
    Role::firstOrCreate(['name' => 'existing-role', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.roles.store'), ['name' => 'existing-role'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('users with permission can view a single role', function (): void {
    $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->getJson(route('api.v1.access-control.roles.show', $role))
        ->assertOk()
        ->assertJsonPath('data.name', 'viewer');
});

test('users with permission can update a role', function (): void {
    $role = Role::firstOrCreate(['name' => 'old-name', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->putJson(route('api.v1.access-control.roles.update', $role), [
            'name' => 'new-name',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'new-name');
});

test('users with permission can soft-delete a role', function (): void {
    $role = Role::firstOrCreate(['name' => 'deletable-role', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->deleteJson(route('api.v1.access-control.roles.destroy', $role))
        ->assertNoContent();

    $this->assertSoftDeleted('roles', ['id' => $role->id]);
});

test('users with permission can restore a soft-deleted role', function (): void {
    $role = Role::firstOrCreate(['name' => 'restorable-role', 'guard_name' => 'web']);
    $role->delete();

    $this->withToken($this->token)
        ->patchJson(route('api.v1.access-control.roles.restore', $role->id))
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null);
});

test('users with permission can permanently delete a role', function (): void {
    $role = Role::firstOrCreate(['name' => 'force-deletable-role', 'guard_name' => 'web']);
    $role->delete();

    $this->withToken($this->token)
        ->deleteJson(route('api.v1.access-control.roles.force-delete', $role->id))
        ->assertNoContent();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});
