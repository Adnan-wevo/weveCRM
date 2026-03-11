<?php

use App\Http\Controllers\Api\V1\Auth\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // ── Authentication ──────────────────────────────────────────────────────
    Route::prefix('auth')->group(function (): void {
        /** @unauthenticated */
        Route::post('token', [TokenController::class, 'store'])->name('api.v1.auth.token.store');
        Route::delete('token', [TokenController::class, 'destroy'])->middleware('auth:sanctum')->name('api.v1.auth.token.destroy');
    });
});
