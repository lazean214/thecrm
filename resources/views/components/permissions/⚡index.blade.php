<?php

use Livewire\Component;
use Spatie\Permission\Models\Permission;

new class extends Component
{
    public $permissions = [];
    public $groupedPermissions = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->permissions = Permission::orderBy('name')->get();
        $this->groupedPermissions = $this->permissions->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);
            return $parts[0] ?? 'other';
        })->sortKeys();
    }

    public function createPermission($name)
    {
        if (!$name) {
            return;
        }

        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        $this->dispatch('notify', type: 'success', message: 'Permission created successfully.');
        $this->loadData();
    }

    public function deletePermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();

        $this->dispatch('notify', type: 'success', message: 'Permission deleted successfully.');
        $this->loadData();
    }
};
?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Permissions</h1>
            <p class="text-sm text-zinc-500 mt-1">Manage system permissions for roles</p>
        </div>
    </div>

    {{-- Create New Permission --}}
    <div class="mb-6">
        <div class="rounded-2xl border bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex gap-3">
                <input type="text"
                       wire:keydown.enter="createPermission($event.target.value)"
                       placeholder="Enter permission name (e.g., manage-users)..."
                       class="flex-1 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 placeholder-zinc-400">
                <button wire:click="createPermission($refs.newPermission.value)"
                        x-on:click="$refs.newPermission.value = ''"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Permission
                </button>
            </div>
        </div>
    </div>

    {{-- Grouped Permissions --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($groupedPermissions as $group => $permissions)
            <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center dark:bg-emerald-900/30">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 uppercase">{{ $group }}</h3>
                        <p class="text-xs text-zinc-500">{{ $permissions->count() }} permissions</p>
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach($permissions as $permission)
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-mono text-zinc-700 dark:text-zinc-300">{{ $permission->name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-zinc-400">{{ $permission->roles->count() }} roles</span>
                                <button wire:click="deletePermission({{ $permission->id }})"
                                        class="text-zinc-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Usage Stats --}}
    <div class="mt-6 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Permission Overview</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-900/20">
                <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $permissions->count() }}</p>
                <p class="text-sm text-indigo-600 dark:text-indigo-400">Total Permissions</p>
            </div>
            <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $groupedPermissions->count() }}</p>
                <p class="text-sm text-purple-600 dark:text-purple-400">Permission Groups</p>
            </div>
            <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-900/20">
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $permissions->sum(fn($p) => $p->roles->count()) }}</p>
                <p class="text-sm text-emerald-600 dark:text-emerald-400">Role Assignments</p>
            </div>
            <div class="rounded-lg bg-amber-50 p-4 dark:bg-amber-900/20">
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $permissions->filter(fn($p) => $p->roles->isEmpty())->count() }}</p>
                <p class="text-sm text-amber-600 dark:text-amber-400">Unused Permissions</p>
            </div>
        </div>
    </div>
</div>
