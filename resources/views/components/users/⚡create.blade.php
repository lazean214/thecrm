<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    public $selectedTeams = [];
    public $teams = [];

    public $open = false;

    public function mount()
    {
        $this->teams = Team::orderBy('name')->get();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'selectedTeams' => 'required|array|min:1',
        ];
    }

    public function createUser()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // Assign selected teams
        $user->teams()->sync($this->selectedTeams);

        session()->flash('message', 'User created successfully.');

        $this->reset([
            'name',
            'email',
            'password',
            'password_confirmation',
            'selectedTeams',
        ]);

        $this->open = false;

        return redirect()->route('users');
    }
};
?>

<div x-data="{ open: $wire.entangle('open') }" class="p-6">

    @if(session()->has('message'))
        <div class="mb-4 rounded-xl bg-green-100 px-4 py-3 text-green-700 dark:bg-green-900/30 dark:text-green-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- Open Modal Button --}}
    <button
        @click="open = true"
        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-indigo-700"
    >
        Create New User
    </button>

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
    >
        <div
            @click.away="open = false"
            class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-900"
        >
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100">
                    Create New User
                </h2>

                <button
                    @click="open = false"
                    class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                >
                    ✕
                </button>
            </div>

            <form wire:submit.prevent="createUser" class="space-y-5">

                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Name
                    </label>

                    <input
                        type="text"
                        wire:model.live="name"
                        class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >

                    @error('name')
                        <p class="text-sm text-red-500 mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Email
                    </label>

                    <input
                        type="email"
                        wire:model.live="email"
                        class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >

                    @error('email')
                        <p class="text-sm text-red-500 mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Team Selection --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Assign Teams
                    </label>

                    <select
                        wire:model.live="selectedTeams"
                        multiple
                        class="w-full rounded-xl border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-zinc-500 mt-1">
                        Hold CTRL / CMD to select multiple teams
                    </p>

                    @error('selectedTeams')
                        <p class="text-sm text-red-500 mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Password
                    </label>

                    <input
                        type="password"
                        wire:model.live="password"
                        class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >

                    @error('password')
                        <p class="text-sm text-red-500 mt-1">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password Confirmation --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        wire:model.live="password_confirmation"
                        class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-3 pt-2">

                    <button
                        type="button"
                        @click="open = false"
                        class="rounded-xl border px-4 py-2 text-sm"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="createUser">
                            Create User
                        </span>

                        <span wire:loading wire:target="createUser">
                            Creating...
                        </span>
                    </button>

                </div>
            </form>
        </div>
    </div>
</div>