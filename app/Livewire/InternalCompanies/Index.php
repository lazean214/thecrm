<?php

namespace App\Livewire\InternalCompanies;

use App\Models\InternalCompany;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $name = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingId = null;
    }

    public function openEdit(int $id): void
    {
        $company = InternalCompany::findOrFail($id);
        $this->editingId = $company->id;
        $this->name = $company->name;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:255']);

        if ($this->editingId) {
            $company = InternalCompany::findOrFail($this->editingId);
            $company->update([
                'name' => trim($this->name),
                'slug' => Str::slug($this->name),
            ]);
            $this->dispatch('notify', type: 'success', message: 'Internal company updated.');
        } else {
            InternalCompany::create([
                'name' => trim($this->name),
                'slug' => Str::slug($this->name),
            ]);
            $this->dispatch('notify', type: 'success', message: 'Internal company created.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        InternalCompany::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Internal company deleted.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->showForm = false;
    }

    public function render()
    {
        $companies = InternalCompany::orderBy('name')->paginate(20);

        return view('livewire.internal-companies.index', compact('companies'));
    }
}
