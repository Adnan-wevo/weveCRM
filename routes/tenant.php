<?php

use App\Http\Controllers\Auth\KeycloakController;
use App\Http\Middleware\EnsureTenantAccess;
use App\Livewire\Auth\MagicLogin;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Routes registered here are loaded by TenancyServiceProvider only when
| TENANCY_MODE is NOT "single".
|
| Modes:
|   subdomain  — identified via {tenant}.yourdomain.com
|   path       — identified via yourdomain.com/{tenant}/…
|
*/

$mode = config('tenancy.mode', 'single');

// ── Subdomain mode ──────────────────────────────────────────────────────
// Tenant identified by subdomain, e.g. acme.yourdomain.com
// Domain constraint ({tenant}.{centralDomain}) ensures these routes only
// match subdomain requests, keeping central-domain routes conflict-free.
if ($mode === 'subdomain') {
    $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';

    Route::domain('{tenant}.'.$centralDomain)
        ->middleware([
            'web',
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ])->group(function () {
            Route::get('/', function () {
                return redirect()->route('tenant.dashboard', ['tenant' => request()->route('tenant')]);
            });

            // ── Auth (guest) ────────────────────────────────────────────────────
            // These GET routes render the existing Livewire auth views under the tenant
            // subdomain. Form POSTs go to central Fortify routes (single-database mode).
            // Login and register set url.intended so Fortify redirects to the tenant
            // dashboard after successful authentication.
            Route::middleware('guest')->group(function () {
                Route::get('login', function () {
                    session(['url.intended' => route('tenant.dashboard', ['tenant' => request()->route('tenant')])]);

                    return view('livewire.auth.login');
                })->name('tenant.login');

                Route::get('register', function () {
                    session([
                        'url.intended' => route('tenant.dashboard', ['tenant' => request()->route('tenant')]),
                        'registering_tenant_id' => tenant('id'),
                    ]);

                    return view('livewire.auth.register');
                })->name('tenant.register');

                Route::get('forgot-password', fn () => view('livewire.auth.forgot-password'))->name('tenant.password.request');
                Route::get('reset-password/{token}', fn () => view('livewire.auth.reset-password'))->name('tenant.password.reset');
                Route::get('magic-login/request', MagicLogin::class)->name('tenant.magic-login.request');

                // ── Tenant Keycloak SSO ─────────────────────────────────────────
                // Sets url.intended before handing off to the central Keycloak
                // controller so the central callback redirects back here after auth.
                // registering_tenant_id is set on both login and register so that
                // brand-new Keycloak users are linked to this tenant regardless of
                // which button they use. The callback always lands on the central
                // domain (enforceCentralCallback), so tenancy is never initialized
                // there and the session key is the only way to carry the tenant.
                Route::get('auth/keycloak/redirect', function () {
                    session([
                        'url.intended' => route('tenant.dashboard', ['tenant' => request()->route('tenant')]),
                        'registering_tenant_id' => tenant('id'),
                    ]);

                    return app(KeycloakController::class)->redirect();
                })->name('tenant.keycloak.redirect');

                Route::get('auth/keycloak/register', function () {
                    session([
                        'url.intended' => route('tenant.dashboard', ['tenant' => request()->route('tenant')]),
                        'registering_tenant_id' => tenant('id'),
                    ]);

                    return app(KeycloakController::class)->registerRedirect();
                })->name('tenant.keycloak.register');
            });

            Route::middleware('auth')->group(function () {
                Route::get('verify-email', fn () => view('livewire.auth.verify-email'))->name('tenant.verification.notice');
                Route::get('confirm-password', fn () => view('livewire.auth.confirm-password'))->name('tenant.password.confirm');
                Route::get('two-factor-challenge', fn () => view('livewire.auth.two-factor-challenge'))->name('tenant.two-factor.login');
            });

            // ── App pages ────────────────────────────────────────────────────────
            Route::view('dashboard', 'tenant/dashboard')
                ->middleware(['auth', 'verified', EnsureTenantAccess::class])
                ->name('tenant.dashboard');

            Route::middleware(['auth', 'verified', EnsureTenantAccess::class])
                ->prefix('access-control')
                ->name('tenant.access-control.')
                ->group(function () {
                    Route::view('users', 'accesscontrol::users')->name('users');
                    Route::view('roles', 'accesscontrol::roles')->name('roles');
                    Route::view('permissions', 'accesscontrol::permissions')->name('permissions');
                });

            Route::middleware(['auth', 'verified', EnsureTenantAccess::class])
                ->prefix('organisation-setup')
                ->name('tenant.organisation-setup.')
                ->group(function () {
                    Route::view('tenants', 'organisation-setup.tenants')->name('tenants');
                });

            // ── Settings ───────────────────────────────────────────────────────────
            Route::middleware(['auth', EnsureTenantAccess::class])->group(function () {
                Route::redirect('settings', 'settings/profile');
                Route::livewire('settings/profile', Profile::class)->name('tenant.profile.edit');
            });

            Route::middleware(['auth', 'verified', EnsureTenantAccess::class])->group(function () {
                Route::livewire('settings/password', Password::class)->name('tenant.user-password.edit');
                Route::livewire('settings/appearance', Appearance::class)->name('tenant.appearance.edit');
                Route::livewire('settings/two-factor', TwoFactor::class)
                    ->middleware(
                        when(
                            Features::canManageTwoFactorAuthentication()
                            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                            ['password.confirm'],
                            [],
                        ),
                    )
                    ->name('tenant.two-factor.show');
            });
        });
}

// ── Path-prefix mode ─────────────────────────────────────────────────
// Tenant identified by path prefix, e.g. yourdomain.com/acme/dashboard
// No PreventAccessFromCentralDomains needed — all domains serve tenant routes.
if ($mode === 'path') {
    Route::prefix('/{tenant}')
        ->middleware([
            'web',
            InitializeTenancyByPath::class,
        ])
        ->group(function () {
            Route::get('/', function () {
                return redirect()->route('tenant.dashboard', ['tenant' => tenant('id')]);
            });

            // ── Auth (guest) ─────────────────────────────────────────────────
            // GET-only: renders the existing Livewire auth views under the tenant
            // path prefix. Form POSTs are handled by central Fortify routes.
            // Login and register set url.intended so Fortify redirects to the
            // tenant dashboard after successful authentication.
            Route::middleware('guest')->group(function () {
                Route::get('login', function () {
                    session(['url.intended' => route('tenant.dashboard', ['tenant' => tenant('id')])]);

                    return view('livewire.auth.login');
                })->name('tenant.login');

                Route::get('register', function () {
                    session([
                        'url.intended' => route('tenant.dashboard', ['tenant' => tenant('id')]),
                        'registering_tenant_id' => tenant('id'),
                    ]);

                    return view('livewire.auth.register');
                })->name('tenant.register');

                Route::get('forgot-password', fn () => view('livewire.auth.forgot-password'))->name('tenant.password.request');
                Route::get('reset-password/{token}', fn () => view('livewire.auth.reset-password'))->name('tenant.password.reset');
                Route::get('magic-login/request', MagicLogin::class)->name('tenant.magic-login.request');

                // ── Tenant Keycloak SSO ──────────────────────────────────────
                // Sets url.intended before handing off to the central Keycloak
                // controller so the central callback redirects back here after auth.
                // registering_tenant_id is set on both login and register so that
                // brand-new Keycloak users are linked to this tenant regardless of
                // which button they use.
                Route::get('auth/keycloak/redirect', function () {
                    session([
                        'url.intended' => route('tenant.dashboard', ['tenant' => tenant('id')]),
                        'registering_tenant_id' => tenant('id'),
                    ]);

                    return app(KeycloakController::class)->redirect();
                })->name('tenant.keycloak.redirect');

                Route::get('auth/keycloak/register', function () {
                    session([
                        'url.intended' => route('tenant.dashboard', ['tenant' => tenant('id')]),
                        'registering_tenant_id' => tenant('id'),
                    ]);

                    return app(KeycloakController::class)->registerRedirect();
                })->name('tenant.keycloak.register');
            });

            Route::middleware('auth')->group(function () {
                Route::get('verify-email', fn () => view('livewire.auth.verify-email'))->name('tenant.verification.notice');
                Route::get('confirm-password', fn () => view('livewire.auth.confirm-password'))->name('tenant.password.confirm');
                Route::get('two-factor-challenge', fn () => view('livewire.auth.two-factor-challenge'))->name('tenant.two-factor.login');
            });

            // ── App pages ────────────────────────────────────────────────────
            Route::view('dashboard', 'tenant/dashboard')
                ->middleware(['auth', 'verified', EnsureTenantAccess::class])
                ->name('tenant.dashboard');

            Route::middleware(['auth', 'verified', EnsureTenantAccess::class])
                ->prefix('access-control')
                ->name('tenant.access-control.')
                ->group(function () {
                    Route::view('users', 'accesscontrol::users')->name('users');
                    Route::view('roles', 'accesscontrol::roles')->name('roles');
                    Route::view('permissions', 'accesscontrol::permissions')->name('permissions');
                });

            Route::middleware(['auth', 'verified', EnsureTenantAccess::class])
                ->prefix('organisation-setup')
                ->name('tenant.organisation-setup.')
                ->group(function () {
                    Route::view('tenants', 'organisation-setup.tenants')->name('tenants');
                });

            // ── Settings ───────────────────────────────────────────────────────
            Route::middleware(['auth', EnsureTenantAccess::class])->group(function () {
                Route::redirect('settings', 'settings/profile');
                Route::livewire('settings/profile', Profile::class)->name('tenant.profile.edit');
            });

            Route::middleware(['auth', 'verified', EnsureTenantAccess::class])->group(function () {
                Route::livewire('settings/password', Password::class)->name('tenant.user-password.edit');
                Route::livewire('settings/appearance', Appearance::class)->name('tenant.appearance.edit');
                Route::livewire('settings/two-factor', TwoFactor::class)
                    ->middleware(
                        when(
                            Features::canManageTwoFactorAuthentication()
                            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                            ['password.confirm'],
                            [],
                        ),
                    )
                    ->name('tenant.two-factor.show');
            });
        });
}
