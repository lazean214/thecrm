<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Team;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public ?User $user = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $selectedTeams = [];
    public $selectedRoles = [];
    public $teams = [];
    public $roles = [];
    public $showModal = false;

    protected $listeners = ['editUser'];

    public function mount()
    {
        $this->teams = Team::orderBy('name')->get();
        $this->roles = Role::orderBy('name')->get();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . ($this->user?->id ?? 'NULL'),
            'selectedTeams' => 'required|array|min:1',
            'selectedRoles' => 'nullable|array',
            'password' => $this->password ? 'nullable|min:8|confirmed' : 'nullable',
        ];
    }

    public function editUser($userId)
    {
        $this->user = User::findOrFail($userId);
        $this->name = $this->user->name;
        $this->email = $this->user->email;

        $this->selectedTeams = $this->user->teams->pluck('id')->toArray();
        $this->selectedRoles = $this->user->roles->pluck('id')->toArray();

        // Reset password fields on fresh load
        $this->password = '';
        $this->password_confirmation = '';

        $this->showModal = true;
    }

    public function update()
    {
        $this->validate();

        $updateData = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $updateData['password'] = Hash::make($this->password);
        }

        $this->user->update($updateData);
        $this->user->teams()->sync($this->selectedTeams);
        $this->user->syncRoles(Role::whereIn('id', $this->selectedRoles)->pluck('name')->toArray());

        $this->showModal = false;
        $this->dispatch('userUpdated');

        session()->flash('message', 'User updated successfully.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['name', 'email', 'selectedTeams', 'selectedRoles', 'password', 'password_confirmation']);
    }
};
?>

<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                
                {{-- Backdrop Layer --}}
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeModal"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                {{-- Modal Box Container --}}
                <div class="inline-block relative z-50 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-zinc-900">
                    <form wire:submit.prevent="update">
                        <div class="px-6 pt-5 pb-4 bg-white dark:bg-zinc-900">
                            <div class="w-full">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-xl font-bold text-zinc-800 dark:text-zinc-100" id="modal-title">
                                        Edit User
                                    </h3>
                                    <button type="button" wire:click="closeModal" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        ✕
                                    </button>
                                </div>
                                
                                <div class="mt-6 space-y-5">
                                    {{-- Name --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Name</label>
                                        <input type="text" wire:model="name" class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    {{-- Email --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Email</label>
                                        <input type="email" wire:model="email" class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Role Selection --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Assign Roles</label>
                                        <select wire:model="selectedRoles" multiple class="w-full rounded-xl border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-purple-500 focus:border-purple-500">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-zinc-500 mt-1">Hold CTRL / CMD to select multiple roles</p>
                                    </div>

                                    {{-- Team Selection --}}
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Assign Teams</label>
                                        <select wire:model="selectedTeams" multiple class="w-full rounded-xl border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                            @foreach($teams as $team)
                                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-zinc-500 mt-1">Hold CTRL / CMD to select multiple teams</p>
                                        @error('selectedTeams') <p class="text-sm text-red-500 mt-1 block">{{ $message }}</p> @enderror
                                    </div>

                                    <hr class="border-zinc-200 dark:border-zinc-800 my-4" />

                                    {{-- Optional Password Area --}}
                                    <div>
                                        <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Update Password</h4>
                                        <p class="text-xs text-zinc-500">Leave blank to keep current system configuration password.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">New Password</label>
                                        <input type="password" wire:model="password" class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Confirm New Password</label>
                                        <input type="password" wire:model="password_confirmation" class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Action Buttons --}}
                        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800 flex justify-end gap-3 rounded-b-2xl">
                            <button wire:click="closeModal" type="button" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-300 dark:border-zinc-600">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>