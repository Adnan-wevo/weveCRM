<?php

use App\Http\Controllers\Auth\KeycloakController;
use App\Http\Controllers\SecureDownloadController;
use App\Http\Middleware\EnsureCentralAccess;
use App\Livewire\Auth\MagicLogin;
use Illuminate\Support\Facades\Route;
use Maize\MagicLogin\Facades\MagicLink;

// In subdomain mode, all central routes must be domain-constrained so that
// requests to tenant subdomains (e.g. wevetel-2.wevetel.test) cannot match
// these routes before tenant initialization middleware runs.
$centralDomain = config('tenancy.mode') === 'subdomain'
    ? (config('tenancy.central_domains')[0] ?? null)
    : null;

$centralRoutes = function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::view('dashboard', 'dashboard')
        ->middleware(['auth', 'verified', EnsureCentralAccess::class])
        ->name('dashboard');

    // ── Central auth views (Fortify handles the POST) ───────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('login', fn () => view('livewire.auth.login'))->name('login');
        Route::get('register', fn () => view('livewire.auth.register'))->name('register');
        Route::get('forgot-password', fn () => view('livewire.auth.forgot-password'))->name('password.request');
        Route::get('reset-password/{token}', fn () => view('livewire.auth.reset-password'))->name('password.reset');
    });

    Route::middleware('auth')->group(function () {
        Route::get('verify-email', fn () => view('livewire.auth.verify-email'))->name('verification.notice');
        Route::get('confirm-password', fn () => view('livewire.auth.confirm-password'))->name('password.confirm');
        Route::get('two-factor-challenge', fn () => view('livewire.auth.two-factor-challenge'))->name('two-factor.login');
    });

    // ── Keycloak OAuth ──────────────────────────────────────────────────────
    // These routes are deliberately outside the auth middleware
    Route::get('/auth/keycloak/redirect', [KeycloakController::class, 'redirect'])->name('keycloak.redirect');
    Route::get('/auth/keycloak/register', [KeycloakController::class, 'registerRedirect'])->name('keycloak.register');
    Route::get('/auth/keycloak/register-fresh', [KeycloakController::class, 'registerFresh'])->name('keycloak.register-fresh');
    Route::get('/auth/keycloak/callback', [KeycloakController::class, 'callback'])->name('keycloak.callback');

    // Keycloak account linking (requires existing authenticated session)
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/auth/keycloak/link', [KeycloakController::class, 'linkRedirect'])->name('keycloak.link');
        Route::get('/auth/keycloak/link/callback', [KeycloakController::class, 'linkCallback'])->name('keycloak.link-callback');
        Route::delete('/auth/keycloak/unlink', [KeycloakController::class, 'unlink'])->name('keycloak.unlink');
    });

    // Keycloak OIDC back-channel logout — called server-to-server by Keycloak.
    // CSRF is exempted via bootstrap/app.php (validateCsrfTokens except).
    Route::post('/auth/keycloak/backchannel-logout', [KeycloakController::class, 'backchannelLogout'])
        ->name('keycloak.backchannel-logout');

    // ── Magic Link (passwordless) ───────────────────────────────────────────
    // /magic-login/request → email form (Livewire component, unauthenticated)
    // /magic-login         → signed link from email, handled by the package
    Route::get('/magic-login/request', MagicLogin::class)
        ->middleware('guest')
        ->name('magic-login.request');
    MagicLink::route();

    // Secure file downloads — exports need import-export permission; media needs auth only
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/secure/exports/{module}/{filename}', [SecureDownloadController::class, 'export'])
            ->name('secure.export')
            ->middleware('signed');

        Route::get('/secure/media/{path}', [SecureDownloadController::class, 'media'])
            ->name('secure.media')
            ->middleware('signed')
            ->where('path', '.+');
    });

    // ── Impersonation (super-admin only) ────────────────────────────────────
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::impersonate();
    });

    require __DIR__.'/settings.php';
};

if ($centralDomain) {
    Route::domain($centralDomain)->group($centralRoutes);
} else {
    $centralRoutes();
}
