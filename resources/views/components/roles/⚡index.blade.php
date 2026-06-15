<?php

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component
{
    public $roles = [];
    public $permissions = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->roles = Role::with('permissions')->orderBy('name')->get();
        $this->permissions = Permission::orderBy('name')->get();
    }

    public function createRole($name)
    {
        if (!$name) {
            return;
        }

        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        $this->dispatch('notify', type: 'success', message: 'Role created successfully.');
        $this->loadData();
    }

    public function deleteRole($roleId)
    {
        $role = Role::findOrFail($roleId);
        $role->delete();

        $this->dispatch('notify', type: 'success', message: 'Role deleted successfully.');
        $this->loadData();
    }

    public function assignPermission($roleId, $permissionId)
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        $role->givePermissionTo($permission);

        $this->dispatch('notify', type: 'success', message: 'Permission assigned.');
        $this->loadData();
    }

    public function removePermission($roleId, $permissionId)
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);
        $role->revokePermissionTo($permission);

        $this->dispatch('notify', type: 'success', message: 'Permission removed.');
        $this->loadData();
    }
};
?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Roles & Permissions</h1>
    </div>

    {{-- Roles Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($roles as $role)
            <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $role->name }}</h2>
                            <p class="text-xs text-zinc-500">{{ $role->permissions->count() }} permissions</p>
                        </div>
                    </div>
                    @if($role->name !== 'admin')
                        <button wire:click="deleteRole({{ $role->id }})"
                                class="text-zinc-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Assigned Permissions --}}
                <div class="mb-4">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-2">Permissions</p>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        @forelse($role->permissions as $permission)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                {{ $permission->name }}
                                <button wire:click="removePermission({{ $role->id }}, {{ $permission->id }})"
                                        class="hover:text-emerald-900 dark:hover:text-emerald-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        @empty
                            <span class="text-xs text-zinc-400 italic">No permissions assigned</span>
                        @endforelse
                    </div>
                </div>

                {{-- Add Permission --}}
                <div>
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-2">Add Permission</p>
                    <select wire:change="assignPermission({{ $role->id }}, $event.target.value)"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <option value="">Select permission...</option>
                        @foreach($permissions as $permission)
                            @if(!$role->hasPermissionTo($permission))
                                <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        @endforeach

        {{-- Create New Role Card --}}
        <div class="rounded-2xl border-2 border-dashed border-zinc-300 p-5 dark:border-zinc-700 flex flex-col items-center justify-center min-h-50">
            <div class="h-12 w-12 rounded-full bg-zinc-100 flex items-center justify-center mb-3 dark:bg-zinc-800">
                <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
            <input type="text"
                   wire:keydown.enter="createRole($event.target.value)"
                   placeholder="New role name..."
                   class="w-full text-center rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 placeholder-zinc-400">
            <p class="text-xs text-zinc-400 mt-2">Press Enter to create</p>
        </div>
    </div>

    {{-- Permissions Reference --}}
    <div class="mt-8">
        <h2 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">All Permissions</h2>
        <div class="rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                @foreach($permissions as $permission)
                    <div class="flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $permission->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
