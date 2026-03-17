<?php

use App\Models\User;
use App\Models\Contact;
use Livewire\Livewire;

it('index is accessible to viewer with crm.view', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $this->actingAs($user)->get(route('crm.contacts.index'))->assertStatus(200);
});

it('creates contact via livewire', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    Livewire::actingAs($user)
        ->test(Modules\CRM\Livewire\Contacts\Create::class)
        ->set('name', 'Test Contact')
        ->set('email', 'test@example.com')
        ->set('phone', '12345')
        ->call('save')
        ->assertRedirect(route('crm.contacts.index'));

    expect(Contact::where('email', 'test@example.com')->exists())->toBeTrue();
});

it('edits contact via livewire', function () {
    $user = User::factory()->create();
    \App\Models\Permission::firstOrCreate(['name' => 'crm.view']);
    $user->givePermissionTo('crm.view');

    $contact = Contact::factory()->create(['owner_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Modules\CRM\Livewire\Contacts\Edit::class, ['contact' => $contact])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertRedirect(route('crm.contacts.index'));

    expect(Contact::find($contact->id)->name)->toBe('Updated Name');
});
