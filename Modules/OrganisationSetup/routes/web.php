<?php

use App\Http\Middleware\EnsureCentralAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureCentralAccess::class])
    ->prefix('organisation-setup')
    ->name('organisation-setup.')
    ->group(function (): void {
        Route::view('tenants', 'organisationsetup::tenants')->name('tenants');
    });
