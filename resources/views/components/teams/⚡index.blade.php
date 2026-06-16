<?php

use Livewire\Component;
use App\Models\Team;
use App\Models\User;

new class extends Component
{
    public $teams;

    protected $listeners = ['teamUpdated' => 'loadData'];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->teams = Team::with('users.roles')->get();
    }
};
?>

<div class="p-6">
    @livewire('teams.edit')

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Teams</h1>
        @livewire('teams.create')
    </div>

    <div class="rounded-2xl border bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left px-4 py-3 text-sm font-medium text-zinc-500">Team</th>
                    <th class="text-left px-4 py-3 text-sm font-medium text-zinc-500">Members</th>
                    <th class="text-right px-4 py-3 text-sm font-medium text-zinc-500"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($teams as $team)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800 last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $team->name }}</p>
                            <p class="text-sm text-zinc-500">{{ $team->description }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @forelse($team->users->take(5) as $user)
                                    <div class="relative" title="{{ $user->name }}">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-medium text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 border-2 border-white dark:border-zinc-900">
                                            {{ $user->initials() }}
                                        </div>
                                        @if($user->roles->count() > 0)
                                            <div class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full bg-purple-500 border-2 border-white dark:border-zinc-900"></div>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-sm text-zinc-400">No members</span>
                                @endforelse
                                @if($team->users->count() > 5)
                                    <span class="text-sm text-zinc-500">+{{ $team->users->count() - 5 }} more</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="$dispatch('editTeam', { teamId: {{ $team->id }} })"
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