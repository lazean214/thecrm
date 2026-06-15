<?php

use Livewire\Component;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public $teams;
    public $users;
    public $roles;

    protected $listeners = ['teamUpdated' => 'loadData'];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->teams = Team::with('users')->get();
        $this->users = User::with(['teams', 'roles'])->get();
        $this->roles = Role::all();
    }

    public function addUserToTeam($teamId, $userId)
    {
        if (!$userId) {
            return;
        }

        $team = Team::findOrFail($teamId);
        $team->users()->syncWithoutDetaching([$userId]);

        $this->dispatch('notify', type: 'success', message: 'User added to team.');
        $this->loadData();
    }

    public function removeUserFromTeam($teamId, $userId)
    {
        $team = Team::findOrFail($teamId);
        $team->users()->detach($userId);

        $this->dispatch('notify', type: 'success', message: 'User removed from team.');
        $this->loadData();
    }

    public function assignRoleToTeam($teamId, $roleId)
    {
        if (!$roleId) {
            return;
        }

        $role = Role::findOrFail($roleId);
        // Assign role to all users in this team
        $team = Team::findOrFail($teamId);
        foreach ($team->users as $user) {
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        $this->dispatch('notify', type: 'success', message: 'Role assigned to all team members.');
        $this->loadData();
    }
};
?>

<div class="p-6">
    @livewire('teams.edit')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Teams</h1>
        @livewire('teams.create')
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($teams as $team)
            <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $team->name }}</h2>
                        <p class="text-sm text-zinc-500">{{ $team->description }}</p>
                    </div>
                    <button wire:click="$dispatch('editTeam', { teamId: {{ $team->id }} })"
                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                </div>

                {{-- Roles for this team type --}}
                <div class="mb-4">
                    <p class="font-medium text-sm mb-2 text-zinc-700 dark:text-zinc-300">
                        Team Role
                    </p>
                    @php
                        $teamRoleMap = [
                            'Sales Team' => 'sales',
                            'Compliance Team' => 'compliance',
                        ];
                        $mappedRole = $teamRoleMap[$team->name] ?? null;
                    @endphp
                    @if($mappedRole)
                        @php $role = $roles->firstWhere('name', $mappedRole); @endphp
                        @if($role)
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                {{ $role->name }}
                            </span>
                        @endif
                    @else
                        <select wire:change="assignRoleToTeam({{ $team->id }}, $event.target.value)"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="">Assign Role to Team</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Members --}}
                <div class="mb-4">
                    <p class="font-medium text-sm mb-2 text-zinc-700 dark:text-zinc-300">
                        Members ({{ $team->users->count() }})
                    </p>
                    <div class="space-y-2">
                        @forelse($team->users as $user)
                            <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-medium text-indigo-700">
                                        {{ $user->initials() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ $user->name }}</p>
                                        <p class="text-xs text-zinc-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @foreach($user->roles as $role)
                                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                    <button wire:click="removeUserFromTeam({{ $team->id }}, {{ $user->id }})"
                                            class="text-zinc-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No members yet</p>
                        @endforelse
                    </div>
                </div>

                {{-- Add Member --}}
                <div>
                    <select wire:change="addUserToTeam({{ $team->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="">+ Add Member</option>
                        @foreach($users as $user)
                            @if(!$team->users->contains($user))
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        @endforeach
    </div>
</div>
