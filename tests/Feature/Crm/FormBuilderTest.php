<?php

use App\Models\Permission;
use App\Models\User;
use Livewire\Livewire;
use Modules\CRM\Models\Form;

it('form builder page is accessible to user with crm.view permission', function () {
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $this->actingAs($user)->get(route('crm.forms.index'))->assertOk();
});

it('creates crm form through livewire form builder', function () {
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\FormBuilder\Index::class)
        ->set('name', 'Lead Capture')
        ->set('schema', '{"fields":[{"name":"email","type":"email"}]}')
        ->call('save');

    expect(Form::query()->where('name', 'Lead Capture')->exists())->toBeTrue();
});
