<?php

use App\Models\User;
use App\Models\Lead;
use Livewire\Livewire;

it('leads index is accessible to user with crm.view', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $this->actingAs($user)->get(route('crm.leads.index'))->assertStatus(200);
});

it('creates lead via livewire', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\Leads\Create::class)
        ->set('source', 'Website')
        ->set('status', 'new')
        ->call('save')
        ->assertRedirect(route('crm.leads.index'));

    expect(Lead::where('source', 'Website')->exists())->toBeTrue();
});

it('edits lead via livewire', function () {
    $user = User::factory()->create();

    $lead = Lead::factory()->create(['owner_id' => $user->id, 'status' => 'new']);

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\Leads\Edit::class, ['lead' => $lead])
        ->set('status', 'qualified')
        ->call('save')
        ->assertRedirect(route('crm.leads.index'));

    expect(Lead::find($lead->id)->status)->toBe('qualified');
});
