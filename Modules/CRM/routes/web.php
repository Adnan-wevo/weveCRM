<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/crm/contacts', \Modules\CRM\Livewire\Contacts\Index::class)->name('crm.contacts.index');
});
