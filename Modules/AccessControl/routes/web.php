<?php

use App\Http\Middleware\EnsureCentralAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureCentralAccess::class])
    ->prefix('access-control')
    ->name('access-control.')
    ->group(function (): void {
        Route::view('users', 'accesscontrol::users')->name('users');
        Route::view('roles', 'accesscontrol::roles')->name('roles');
        Route::view('permissions', 'accesscontrol::permissions')->name('permissions');
    });
