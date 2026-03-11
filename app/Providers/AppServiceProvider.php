<?php

namespace App\Providers;

use App\Listeners\RecordImpersonationEnd;
use App\Socialite\KeycloakProvider;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Lab404\Impersonate\Events\LeaveImpersonation;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Keycloak Socialite driver using a custom provider that
        // decodes the JWT payload locally instead of calling the userinfo endpoint.
        // Using the SocialiteWasCalled event ensures the ConfigRetriever is wired
        // up correctly so getConfig('base_url') reads services.keycloak.base_url.
        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', KeycloakProvider::class);
        });

        // Record when an impersonation session ends for a complete audit trail.
        Event::listen(LeaveImpersonation::class, RecordImpersonationEnd::class);

        // Implicitly grant "super-admin" role all permissions via Gate::before.
        // Uses null (not false) so normal policy checks still run for other roles.
        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
