<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('AI Assistant')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5 text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300" icon="sparkles" href="#" x-on:click.prevent="$dispatch('toggle-ai-assistant')" :label="__('AI Assistant')" />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
                <livewire:notifications-dropdown />
            </flux:sidebar.header>

            <flux:sidebar.nav>

                {{-- CRM --}}
                <flux:sidebar.group :heading="__('CRM')">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-storefront" :href="route('deals')" :current="request()->routeIs('deals*')" wire:navigate>
                        {{ __('Deals') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('contacts')" :current="request()->routeIs('contacts*')" wire:navigate>
                        {{ __('Contacts') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office" :href="route('companies')" :current="request()->routeIs('companies*')" wire:navigate>
                        {{ __('Companies') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" href="#" x-on:click.prevent="$dispatch('toggle-ai-assistant')">
                        {{ __('AI Assistant') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Tools --}}
                <flux:sidebar.group :heading="__('Tools')">
                    <flux:sidebar.item icon="envelope" :href="route('envelopes.index')" :current="request()->routeIs('envelopes*')" wire:navigate>
                        {{ __('Envelopes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="pencil-square" :href="route('designer')" :current="request()->routeIs('designer*')" wire:navigate>
                        {{ __('Email Designer') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Administration --}}
                <flux:sidebar.group :heading="__('Administration')">
                    <flux:sidebar.item icon="user-circle" :href="route('users')" :current="request()->routeIs('users*')" wire:navigate>
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="rectangle-group" :href="route('teams')" :current="request()->routeIs('teams*')" wire:navigate>
                        {{ __('Teams') }}
                    </flux:sidebar.item>
                    @can('manage-gdpr')
                        <flux:sidebar.item icon="shield-check" :href="route('admin.gdpr.dashboard')" :current="request()->routeIs('admin.gdpr*')" wire:navigate>
                            {{ __('GDPR Compliance') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                {{-- My Account --}}
                <flux:sidebar.group :heading="__('My Account')">
                    <flux:sidebar.item icon="arrow-down-tray" :href="route('gdpr.export.form')" :current="request()->routeIs('gdpr.export*')" wire:navigate>
                        {{ __('Request My Data') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'appearance.edit', 'security.edit')" wire:navigate>
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu />
        </flux:sidebar>

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
