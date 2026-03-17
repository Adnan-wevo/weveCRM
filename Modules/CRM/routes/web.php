<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    // Contacts
    Route::get('/crm/contacts', \Modules\CRM\Livewire\Contacts\Index::class)->name('crm.contacts.index');
    Route::get('/crm/contacts/create', \Modules\CRM\Livewire\Contacts\Create::class)->name('crm.contacts.create');
    Route::get('/crm/contacts/{contact}/edit', \Modules\CRM\Livewire\Contacts\Edit::class)->name('crm.contacts.edit');

    // Leads
    Route::get('/crm/leads', \Modules\CRM\Livewire\Leads\Index::class)->name('crm.leads.index');
    Route::get('/crm/leads/create', \Modules\CRM\Livewire\Leads\Create::class)->name('crm.leads.create');
    Route::get('/crm/leads/{lead}/edit', \Modules\CRM\Livewire\Leads\Edit::class)->name('crm.leads.edit');

    // Form Builder
    Route::get('/crm/forms', \Modules\CRM\Livewire\FormBuilder\Index::class)->name('crm.forms.index');
});
