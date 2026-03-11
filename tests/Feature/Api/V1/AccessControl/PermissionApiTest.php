<?php

use App\Models\Permission;
use App\Models\User;

beforeEach(function (): void {
    $role = \App\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    foreach (['index', 'store', 'show', 'update', 'destroy', 'restore', 'force-delete'] as $action) {
        $role->givePermissionTo(
            Permission::firstOrCreate(['name' => "access-control.permissions.{$action}", 'guard_name' => 'web'])
        );
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->token = $this->admin->createToken('test')->plainTextToken;
});

test('unauthenticated users cannot list permissions', function (): void {
    $this->getJson(route('api.v1.access-control.permissions.index'))
        ->assertUnauthorized();
});

test('authenticated users without permission cannot list permissions', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.v1.access-control.permissions.index'))
        ->assertForbidden();
});

test('users with permission can list permissions', function (): void {
    $this->withToken($this->token)
        ->getJson(route('api.v1.access-control.permissions.index'))
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('users with permission can create a permission', function (): void {
    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.permissions.store'), [
            'name' => 'reports.view',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'reports.view');
});

test('creating a permission fails when name is missing', function (): void {
    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.permissions.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('creating a permission fails when name already exists', function (): void {
    Permission::firstOrCreate(['name' => 'existing.permission', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->postJson(route('api.v1.access-control.permissions.store'), ['name' => 'existing.permission'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('users with permission can view a single permission', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'reports.index', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->getJson(route('api.v1.access-control.permissions.show', $permission))
        ->assertOk()
        ->assertJsonPath('data.name', 'reports.index');
});

test('users with permission can update a permission', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'old.permission', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->putJson(route('api.v1.access-control.permissions.update', $permission), [
            'name' => 'new.permission',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'new.permission');
});

test('users with permission can soft-delete a permission', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'deletable.permission', 'guard_name' => 'web']);

    $this->withToken($this->token)
        ->deleteJson(route('api.v1.access-control.permissions.destroy', $permission))
        ->assertNoContent();

    $this->assertSoftDeleted('permissions', ['id' => $permission->id]);
});

test('users with permission can restore a soft-deleted permission', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'restorable.permission', 'guard_name' => 'web']);
    $permission->delete();

    $this->withToken($this->token)
        ->patchJson(route('api.v1.access-control.permissions.restore', $permission->id))
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null);
});

test('users with permission can permanently delete a permission', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'force-deletable.permission', 'guard_name' => 'web']);
    $permission->delete();

    $this->withToken($this->token)
        ->deleteJson(route('api.v1.access-control.permissions.force-delete', $permission->id))
        ->assertNoContent();

    $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
});
