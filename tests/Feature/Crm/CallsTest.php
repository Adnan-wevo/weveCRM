<?php

use App\Models\Contact;
use App\Models\User;
use Livewire\Livewire;
use Modules\CRM\Models\Call;

it('calls index is accessible to user with crm.view', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $this->actingAs($user)->get(route('crm.calls.index'))->assertStatus(200);
});

it('creates call log via livewire', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\Calls\Create::class)
        ->set('contact_id', $contact->id)
        ->set('direction', 'outbound')
        ->set('duration', '120')
        ->set('notes', 'Client follow-up call')
        ->call('save')
        ->assertRedirect(route('crm.calls.index'));

    expect(Call::where('notes', 'Client follow-up call')->exists())->toBeTrue();
});

it('owner can delete their call log', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $call = Call::create([
        'user_id' => $user->id,
        'direction' => 'outbound',
        'duration' => 60,
        'notes' => 'Temporary call',
        'called_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(\Modules\CRM\Livewire\Calls\Index::class)
        ->call('deleteCall', $call->id);

    expect(Call::withTrashed()->find($call->id)->deleted_at)->not()->toBeNull();
});
