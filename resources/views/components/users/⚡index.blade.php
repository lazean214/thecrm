<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Team;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public $users = [];
    public $teams = [];
    public $roles = [];

    protected $listeners = ['userUpdated' => 'loadData'];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->users = User::with(['teams', 'roles'])->get();
        $this->teams = Team::all();
        $this->roles = Role::all();
    }

    public function assignTeam($userId, $teamId)
    {
        if (!$teamId) {
            return;
        }

        $user = User::findOrFail($userId);
        $user->teams()->syncWithoutDetaching([$teamId]);

        $this->dispatch('notify', type: 'success', message: 'Team assigned successfully.');
        $this->loadData();
    }

    public function removeTeam($userId, $teamId)
    {
        $user = User::findOrFail($userId);
        $user->teams()->detach($teamId);

        $this->dispatch('notify', type: 'success', message: 'Team removed successfully.');
        $this->loadData();
    }

    public function assignRole($userId, $roleId)
    {
        if (!$roleId) {
            return;
        }

        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        $user->assignRole($role);

        $this->dispatch('notify', type: 'success', message: 'Role assigned successfully.');
        $this->loadData();
    }

    public function removeRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        $user->removeRole($role);

        $this->dispatch('notify', type: 'success', message: 'Role removed successfully.');
        $this->loadData();
    }
};
?>

<div class="p-6">
    @livewire('users.edit')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">
            Users
        </h1>

        @livewire('users.create')
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($users as $user)
            <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-lg font-bold">
                                {{ $user->name }}
                            </h2>
                            <p class="text-sm text-zinc-500">
                                {{ $user->email }}
                            </p>
                        </div>
                        <button wire:click="$dispatch('editUser', { userId: {{ $user->id }} })"
                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Roles --}}
                <div class="mb-4">
                    <p class="font-medium text-sm mb-2 text-zinc-700 dark:text-zinc-300">
                        Roles
                    </p>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @forelse($user->roles as $role)
                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                {{ $role->name }}
                                <button wire:click="removeRole({{ $user->id }}, {{ $role->id }})" class="hover:text-purple-900">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        @empty
                            <span class="text-sm text-zinc-500">No roles assigned</span>
                        @endforelse
                    </div>
                    <select wire:change="assignRole({{ $user->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="">Assign Role</option>
                        @foreach($roles as $role)
                            @if(!$user->hasRole($role))
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- Teams --}}
                <div>
                    <p class="font-medium text-sm mb-2 text-zinc-700 dark:text-zinc-300">
                        Teams
                    </p>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @forelse($user->teams as $team)
                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                {{ $team->name }}
                                <button wire:click="removeTeam({{ $user->id }}, {{ $team->id }})" class="hover:text-indigo-900">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        @empty
                            <span class="text-sm text-zinc-500">No team assigned</span>
                        @endforelse
                    </div>
                    <select wire:change="assignTeam({{ $user->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="">Assign Team</option>
                        @foreach($teams as $team)
                            @if(!$user->teams->contains($team))
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        @endforeach
    </div>
</div>
