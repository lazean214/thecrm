<?php

use Livewire\Component;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public ?Team $team = null;
    public $name = '';
    public $description = '';
    public $selectedUsers = [];
    public $selectedRoles = [];
    public $users = [];
    public $roles = [];
    public $showModal = false;

    // Listen for the editTeam event dispatched from the index file
    protected $listeners = ['editTeam'];

    public function mount()
    {
        // Cache available users once to populate the member list
        $this->users = User::orderBy('name')->get();
        $this->roles = Role::orderBy('name')->get();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'selectedUsers' => 'nullable|array',
            'selectedRoles' => 'nullable|array',
        ];
    }

    public function editTeam($teamId)
    {
        $this->team = Team::with('users.roles')->findOrFail($teamId);
        $this->name = $this->team->name;
        $this->description = $this->team->description;

        // Pluck the IDs of all current members assigned to this team
        $this->selectedUsers = $this->team->users->pluck('id')->toArray();

        // Get unique roles from all team members
        $this->selectedRoles = $this->team->users->flatMap->roles->pluck('id')->unique()->toArray();

        $this->showModal = true;
    }

    public function update()
    {
        $this->validate();

        $this->team->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        // Sync the chosen members to the pivot relationship
        $this->team->users()->sync($this->selectedUsers);

        // Update roles for all team members
        if (!empty($this->selectedRoles)) {
            $roles = Role::whereIn('id', $this->selectedRoles)->get();
            foreach ($this->team->users as $user) {
                $user->syncRoles($roles);
            }
        }

        $this->showModal = false;

        // Dispatch the refresh event to notify the main list
        $this->dispatch('teamUpdated');

        session()->flash('message', 'Team updated successfully.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['name', 'description', 'selectedUsers', 'selectedRoles']);
    }
};
?>

<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                
                {{-- Backdrop layer with modern blur --}}
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeModal"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                {{-- Modal Box Container --}}
                <div class="inline-block relative z-50 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-zinc-900">
                    <form wire:submit.prevent="update">
                        <div class="px-6 pt-5 pb-4 bg-white dark:bg-zinc-900">
                            <div class="w-full">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-xl font-bold text-zinc-800 dark:text-zinc-100" id="modal-title">
                                        Edit Team
                                    </h3>
                                    <button type="button" wire:click="closeModal" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        ✕
                                    </button>
                                </div>
                                
                                <div class="mt-6 space-y-5">
                                    {{-- Team Name --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Team Name</label>
                                        <input type="text" wire:model="name" class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Description --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Description</label>
                                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                        @error('description') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Roles Selection --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Team Roles</label>
                                        <select wire:model="selectedRoles" multiple class="w-full rounded-xl border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-zinc-500 mt-1">These roles will be assigned to all team members</p>
                                    </div>

                                    {{-- Members Selection --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Manage Members</label>
                                        <select wire:model="selectedUsers" multiple class="w-full rounded-xl border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-zinc-500 mt-1">Hold CTRL / CMD to select multiple team members</p>
                                        @error('selectedUsers') <p class="text-sm text-red-500 mt-1 block">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Action Buttons --}}
                        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800 flex justify-end gap-3 rounded-b-2xl">
                            <button wire:click="closeModal" type="button" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 bg-white hover:bg-gray-50 dark:bg-zinc-700 dark:text-gray-300 dark:border-zinc-600">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Update Team
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>