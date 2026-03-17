<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        {{-- Expose tenant context so app.js subscribes to the correct broadcast channel. --}}
        <script>window.wevetelTenantId = @json(tenancy()->initialized ? tenant('id') : null);</script>
    </head>
    <body class="h-dvh bg-zinc-50 dark:bg-zinc-900">
        @php
            $isTenant   = tenancy()->initialized;
            // Resolve the {tenant} route parameter for the current tenancy mode:
            //   subdomain → slug from the request route (e.g. "acme")
            //   path      → tenant ID used as path prefix (e.g. "x1")
            //   single    → null (no tenant routes exist)
            $tenantParam  = $isTenant
                ? (config('tenancy.mode') === 'path' ? tenant('id') : request()->route('tenant'))
                : null;
            $tenantParams = $tenantParam ? ['tenant' => $tenantParam] : [];

            $dashboardRoute     = $isTenant ? 'tenant.dashboard'                  : 'dashboard';
            $usersRoute         = $isTenant ? 'tenant.access-control.users'       : 'access-control.users';
            $rolesRoute         = $isTenant ? 'tenant.access-control.roles'       : 'access-control.roles';
            $permissionsRoute   = $isTenant ? 'tenant.access-control.permissions' : 'access-control.permissions';
            $tenantsRoute       = $isTenant ? 'tenant.organisation-setup.tenants' : 'organisation-setup.tenants';
            $profileRoute       = ($isTenant && $tenantParam) ? route('tenant.profile.edit', $tenantParams) : route('profile.edit');
        @endphp

        <flux:sidebar sticky collapsible="mobile" class="wevetel-sidebar border-e border-[#1a7a9e] bg-[#0c4d65] dark:border-[#0a3d52] dark:bg-[#072d3d]">
            <flux:sidebar.header class="sticky top-0 z-10 px-3 py-2 bg-[#0c4d65] dark:bg-[#072d3d]">
                <div class="flex w-full items-center justify-center">
                    <x-app-logo :sidebar="true" />
                </div>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @if ($isTenant)
                <div class="mx-3 mb-1 flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2">
                    <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white">
                        {{ strtoupper(substr((string) tenant('id'), 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold text-white">{{ tenant()->name ?? tenant('id') }}</p>
                        <p class="truncate text-[10px] text-[#cde9f4]/70">{{ tenant('id') }}</p>
                    </div>
                </div>
            @endif

            <flux:sidebar.nav class="mt-0">
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route($dashboardRoute, $tenantParams)" :current="request()->routeIs($dashboardRoute)" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @canany(['access-control.users.index', 'access-control.roles.index', 'access-control.permissions.index'])
                <flux:sidebar.group :heading="__('Access Control')" class="grid">
                    @can('access-control.users.index')
                    <flux:sidebar.item icon="users" :href="route($usersRoute, $tenantParams)" :current="request()->routeIs($usersRoute)" wire:navigate>
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('access-control.roles.index')
                    <flux:sidebar.item icon="shield-check" :href="route($rolesRoute, $tenantParams)" :current="request()->routeIs($rolesRoute)" wire:navigate>
                        {{ __('Roles') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('access-control.permissions.index')
                    <flux:sidebar.item icon="key" :href="route($permissionsRoute, $tenantParams)" :current="request()->routeIs($permissionsRoute)" wire:navigate>
                        {{ __('Permissions') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endcanany

                @can('organisation-setup.tenants')
                <flux:sidebar.group :heading="__('Organisation Setup')" class="grid">
                    <flux:sidebar.item icon="building-office" :href="route($tenantsRoute, $tenantParams)" :current="request()->routeIs($tenantsRoute)" wire:navigate>
                        {{ __('Tenants') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcan

                @can('crm.view')
                    <flux:sidebar.group :heading="__('CRM')" class="grid">
                        <flux:sidebar.item icon="layout-grid" :href="route('crm.dashboard', $tenantParams)" :current="request()->routeIs('crm.dashboard')" wire:navigate>
                            {{ __('CRM Dashboard') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="book-open-text" :href="route('crm.contacts.index', $tenantParams)" :current="request()->routeIs('crm.contacts.*')" wire:navigate>
                            {{ __('Contacts') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="layout-grid" :href="route('crm.leads.index', $tenantParams)" :current="request()->routeIs('crm.leads.*')" wire:navigate>
                            {{ __('Leads') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="folder-git-2" :href="route('crm.forms.index', $tenantParams)" :current="request()->routeIs('crm.forms.*')" wire:navigate>
                            {{ __('Forms') }}
                        </flux:sidebar.item>
                            <flux:sidebar.item icon="chevrons-up-down" :href="route('crm.pipeline.index', $tenantParams)" :current="request()->routeIs('crm.pipeline.*')" wire:navigate>
                                {{ __('Pipeline') }}
                            </flux:sidebar.item>
                                <flux:sidebar.item icon="book-open-text" :href="route('crm.calls.index', $tenantParams)" :current="request()->routeIs('crm.calls.*')" wire:navigate>
                                    {{ __('Calls') }}
                                </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            @impersonating
            <div class="mx-3 mb-2 rounded-lg bg-amber-500/20 px-3 py-2 ring-1 ring-amber-400/40">
                <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-amber-300">
                    {{ __('Impersonating') }}
                </p>
                <p class="truncate text-xs font-medium text-white">
                    {{ auth()->user()->name }}
                </p>
                <a href="{{ route('impersonate.leave') }}" class="mt-2 flex w-full items-center justify-center gap-1 rounded-md bg-amber-500/30 px-2 py-1 text-xs font-semibold text-amber-200 transition hover:bg-amber-500/50">
                    <x-flux::icon name="arrow-left-start-on-rectangle" class="size-3.5" />
                    {{ __('Leave Impersonation') }}
                </a>
            </div>
            @endImpersonating

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="bg-[#0c4d65] border-b border-[#1a7a9e] dark:bg-[#072d3d] dark:border-[#0a3d52]">
            <flux:sidebar.toggle class="lg:hidden !text-white !font-bold hover:!bg-white/20 rounded-md" icon="bars-3" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="$profileRoute" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        {{-- Pass the tenant login URL so LogoutResponse can redirect back there. --}}
                        @if ($isTenant)
                            <input type="hidden" name="_tenant_login" value="{{ route('tenant.login', $tenantParams) }}">
                        @endif
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @include('partials.toast')

        @fluxScripts
    </body>
</html>
