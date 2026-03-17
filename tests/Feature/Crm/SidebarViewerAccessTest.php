<?php

use App\Models\Permission;
use App\Models\User;

it('shows crm sidebar items for user with crm.view permission', function () {
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Contacts');
    $response->assertSee('Leads');
    $response->assertSee('Forms');
});

it('hides crm sidebar items for user without crm.view permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('Forms');
});
