<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\CRM\Models\Deal;

it('pipeline index is accessible to user with crm.view', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $this->actingAs($user)->get(route('crm.pipeline.index'))->assertStatus(200);
});

it('creates deal via livewire', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\Pipeline\Create::class)
        ->set('title', 'New Enterprise Deal')
        ->set('stage', 'new')
        ->set('status', 'open')
        ->set('value', '5000')
        ->set('currency', 'MYR')
        ->call('save')
        ->assertRedirect(route('crm.pipeline.index'));

    expect(Deal::where('title', 'New Enterprise Deal')->exists())->toBeTrue();
});

it('edits deal via livewire', function () {
    $user = User::factory()->create();

    $deal = Deal::factory()->create(['owner_id' => $user->id, 'stage' => 'new']);

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\Pipeline\Edit::class, ['deal' => $deal])
        ->set('stage', 'qualified')
        ->call('save')
        ->assertRedirect(route('crm.pipeline.index'));

    expect(Deal::find($deal->id)->stage)->toBe('qualified');
});

it('owner can delete their deal', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $deal = Deal::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\Pipeline\Index::class)
        ->call('deleteDeal', $deal->id);

    expect(Deal::withTrashed()->find($deal->id)->deleted_at)->not()->toBeNull();
});
