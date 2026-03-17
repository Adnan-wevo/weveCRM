<?php

use App\Models\Permission;
use App\Models\User;

it('crm dashboard is accessible to user with crm.view permission', function () {
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $this->actingAs($user)->get(route('crm.dashboard'))->assertOk();
});
