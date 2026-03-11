<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessControl\Http\Controllers\Api\V1\PermissionController;
use Modules\AccessControl\Http\Controllers\Api\V1\RoleController;
use Modules\AccessControl\Http\Controllers\Api\V1\UserController;

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth:sanctum')->prefix('access-control')->group(function (): void {

        // Users
        Route::get('users', [UserController::class, 'index'])->name('v1.access-control.users.index');
        Route::post('users', [UserController::class, 'store'])->name('v1.access-control.users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('v1.access-control.users.show');
        Route::put('users/{user}', [UserController::class, 'update'])->name('v1.access-control.users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('v1.access-control.users.destroy');
        Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('v1.access-control.users.restore');
        Route::delete('users/{user}/force', [UserController::class, 'forceDelete'])->name('v1.access-control.users.force-delete');

        // Roles
        Route::get('roles', [RoleController::class, 'index'])->name('v1.access-control.roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('v1.access-control.roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('v1.access-control.roles.show');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('v1.access-control.roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('v1.access-control.roles.destroy');
        Route::patch('roles/{role}/restore', [RoleController::class, 'restore'])->name('v1.access-control.roles.restore');
        Route::delete('roles/{role}/force', [RoleController::class, 'forceDelete'])->name('v1.access-control.roles.force-delete');

        // Permissions
        Route::get('permissions', [PermissionController::class, 'index'])->name('v1.access-control.permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('v1.access-control.permissions.store');
        Route::get('permissions/{permission}', [PermissionController::class, 'show'])->name('v1.access-control.permissions.show');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('v1.access-control.permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('v1.access-control.permissions.destroy');
        Route::patch('permissions/{permission}/restore', [PermissionController::class, 'restore'])->name('v1.access-control.permissions.restore');
        Route::delete('permissions/{permission}/force', [PermissionController::class, 'forceDelete'])->name('v1.access-control.permissions.force-delete');
    });
});
