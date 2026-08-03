<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
            <livewire:notifications-dropdown />
        </flux:sidebar.header>

        <flux:sidebar.nav>

            {{-- CRM --}}
            <flux:sidebar.group :heading="__('CRM')">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-storefront" :href="route('deals')"
                    :current="request()->routeIs('deals*')" wire:navigate>
                    {{ __('Deals') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="user-group" :href="route('contacts')" :current="request()->routeIs('contacts*')"
                    wire:navigate>
                    {{ __('Contacts') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-office" :href="route('companies')"
                    :current="request()->routeIs('companies*')" wire:navigate>
                    {{ __('Companies') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="sparkles" href="#" x-on:click.prevent="$dispatch('toggle-ai-assistant')">
                    {{ __('AI Assistant') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            {{-- Remittances --}}
            <flux:sidebar.group :heading="__('Remittances')">
                <flux:sidebar.item icon="currency-pound" :href="route('remittances.index')"
                    :current="request()->routeIs('remittances.index')" wire:navigate>
                    {{ __('Tracker') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-storefront" :href="route('remittances.report')"
                    :current="request()->routeIs('remittances.report')" wire:navigate>
                    {{ __('Report') }}
                </flux:sidebar.item>

            </flux:sidebar.group>

            {{-- Tools --}}
            <flux:sidebar.group :heading="__('Tools')">
                <flux:sidebar.item icon="envelope" :href="route('envelopes.index')"
                    :current="request()->routeIs('envelopes*')" wire:navigate>
                    {{ __('Envelopes') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="pencil-square" :href="route('designer')"
                    :current="request()->routeIs('designer*')" wire:navigate>
                    {{ __('Email Designer') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="chart-bar-square" :href="route('reports')"
                    :current="request()->routeIs('reports')" wire:navigate>
                    {{ __('Reports') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            @can('view-users')
                {{-- Administration --}}
                <flux:sidebar.group :heading="__('Administration')">
                    <flux:sidebar.item icon="user-circle" :href="route('users')" :current="request()->routeIs('users*')"
                        wire:navigate>
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="home" :href="route('internal-companies')"
                        :current="request()->routeIs('internal-companies*')" wire:navigate>
                        {{ __('Internal Companies') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="rectangle-group" :href="route('teams')"
                        :current="request()->routeIs('teams*')" wire:navigate>
                        {{ __('Teams') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="lock-closed" :href="route('roles')" :current="request()->routeIs('roles*')"
                        wire:navigate>
                        {{ __('Roles') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="lock-open" :href="route('permissions')"
                        :current="request()->routeIs('permissions*')" wire:navigate>
                        {{ __('Permissions') }}
                    </flux:sidebar.item>
                    @can('manage-gdpr')
                        <flux:sidebar.item icon="shield-check" :href="route('admin.gdpr.dashboard')"
                            :current="request()->routeIs('admin.gdpr*')" wire:navigate>
                            {{ __('GDPR Compliance') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcan

            {{-- My Account --}}
            <flux:sidebar.group :heading="__('My Account')">
                <flux:sidebar.item icon="question-mark-circle" :href="route('help')"
                    :current="request()->routeIs('help')" wire:navigate>
                    {{ __('User Guide') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="arrow-down-tray" :href="route('gdpr.export.form')"
                    :current="request()->routeIs('gdpr.export*')" wire:navigate>
                    {{ __('Request My Data') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')"
                    :current="request()->routeIs('profile.edit', 'appearance.edit', 'security.edit', 'notifications.edit', 'data.edit', 'fiscal-year.edit')"
                    wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

        </flux:sidebar.nav>

        <flux:spacer />
        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile Menu -->
    <flux:header class="lg:hidden">

        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />
        <livewire:notifications-dropdown />
        <flux:dropdown position="top" align="end">

            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    <livewire:ai.assistant-panel />

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
