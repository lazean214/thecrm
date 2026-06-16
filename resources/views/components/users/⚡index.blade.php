<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Team;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public $users = [];

    protected $listeners = ['userUpdated' => 'loadData'];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->users = User::with(['teams', 'roles'])->get();
    }
};
?>

<div class="p-6">
    @livewire('users.edit')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Users</h1>
        @livewire('users.create')
    </div>

    <div class="rounded-2xl border bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left px-4 py-3 text-sm font-medium text-zinc-500">User</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-zinc-500">Roles</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-zinc-500">Teams</th>
                    <th class="text-right px-4 py-3 text-sm font-medium text-zinc-500"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800 last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-medium text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                    {{ $user->initials() }}
                                </div>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</p>
                                    <p class="text-sm text-zinc-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->roles as $role)
                                    <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-sm text-zinc-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->teams as $team)
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                        {{ $team->name }}
                                    </span>
                                @empty
                                    <span class="text-sm text-zinc-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="$dispatch('editUser', { userId: {{ $user->id }} })"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 p-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>