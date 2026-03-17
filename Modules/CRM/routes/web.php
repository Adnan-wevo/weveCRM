<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    // Dashboard
    Route::get('/crm/dashboard', \Modules\CRM\Livewire\Dashboard\Index::class)->name('crm.dashboard');

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

        // Pipeline / Deals
        Route::get('/crm/pipeline', \Modules\CRM\Livewire\Pipeline\Index::class)->name('crm.pipeline.index');
        Route::get('/crm/pipeline/create', \Modules\CRM\Livewire\Pipeline\Create::class)->name('crm.pipeline.create');
        Route::get('/crm/pipeline/{deal}/edit', \Modules\CRM\Livewire\Pipeline\Edit::class)->name('crm.pipeline.edit');

            // Calls
            Route::get('/crm/calls', \Modules\CRM\Livewire\Calls\Index::class)->name('crm.calls.index');
            Route::get('/crm/calls/create', \Modules\CRM\Livewire\Calls\Create::class)->name('crm.calls.create');
});
