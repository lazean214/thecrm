<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    @fluxScripts
</head>

<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:grid-cols-2 lg:px-0">
        <!-- Left Panel - Branding -->
        <div
            class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800">
            <div class="absolute inset-0 bg-neutral-900 text-white opacity-75"
                style="background-image: url('https://img.magnific.com/free-vector/gradient-metaverse-illustration_23-2149265633.jpg'); background-size: cover; background-position: center;">
            </div>
            <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                <span class="flex  w-24 items-center justify-center rounded-md">
                    <x-app-logo-icon class="me-2 h-7 fill-current text-white" />
                </span>

            </a>

            @php
                [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
            @endphp

            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <flux:heading size="lg">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                    <footer>
                        <flux:heading>{{ trim($author) }}</flux:heading>
                    </footer>
                </blockquote>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="w-full lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-87.5">
                <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden"
                    wire:navigate>
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <!-- Login Form -->
                <div class="flex flex-col gap-6">
                    <flux:heading size="lg">Welcome Back</flux:heading>
                    <flux:text variant="muted">Sign in to your account to continue</flux:text>

                    <!-- Session Status -->
                    @if (session('status'))
                        <flux:callout variant="success" icon="check-circle">
                            {{ session('status') }}
                        </flux:callout>
                    @endif

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <flux:callout variant="danger" icon="exclamation-circle">
                            <ul class="list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </flux:callout>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
                        @csrf

                        <!-- Email Address -->
                        <flux:field>
                            <flux:label>Email address</flux:label>
                            <flux:input name="email" type="email" :value="old('email')" required autofocus
                                autocomplete="email" placeholder="email@example.com" />
                        </flux:field>

                        <!-- Password -->
                        <flux:field>
                            <flux:label>Password</flux:label>
                            <flux:input name="password" type="password" required autocomplete="current-password"
                                placeholder="Password" viewable />
                            @if (Route::has('password.request'))
                                <flux:link class="mt-1" :href="route('password.request')" wire:navigate>
                                    Forgot your password?
                                </flux:link>
                            @endif
                        </flux:field>

                        <!-- Remember Me -->
                        <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                        <flux:button variant="primary" type="submit" class="w-full">
                            {{ __('Log in') }}
                        </flux:button>
                    </form>

                    @if (Route::has('register') && auth()->check() && auth()->user()->isAdmin())
                        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                            <span>Don't have an account?</span>
                            <flux:link :href="route('register')" wire:navigate>Sign up</flux:link>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
</body>

</html>
