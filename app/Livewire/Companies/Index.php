<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreate = false;

    public bool $showEdit = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $domain = '';

    public string $phone = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'domain' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showCreate = true;
        $this->showEdit = false;
    }

    public function openEdit(int $id): void
    {
        $company = Company::findOrFail($id);
        $this->editingId = $company->id;
        $this->name = $company->name;
        $this->email = $company->email ?? '';
        $this->domain = $company->domain ?? '';
        $this->phone = $company->phone ?? '';
        $this->showEdit = true;
        $this->showCreate = false;
    }

    public function save(): void
    {
        $this->validate();

        Company::create([
            'name' => $this->name,
            'email' => $this->email ?: null,
            'domain' => $this->domain ?: null,
            'phone' => $this->phone ?: null,
        ]);

        $this->resetForm();
        $this->showCreate = false;
        $this->dispatch('notify', type: 'success', message: 'Company created.');
    }

    public function update(): void
    {
        $this->validate();

        Company::findOrFail($this->editingId)->update([
            'name' => $this->name,
            'email' => $this->email ?: null,
            'domain' => $this->domain ?: null,
            'phone' => $this->phone ?: null,
        ]);

        $this->resetForm();
        $this->showEdit = false;
        $this->dispatch('notify', type: 'success', message: 'Company updated.');
    }

    public function delete(int $id): void
    {
        Company::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Company deleted.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showCreate = false;
        $this->showEdit = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->domain = '';
        $this->phone = '';
    }

    public function render()
    {
        $companies = Company::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
            ->orWhere('email', 'like', "%{$this->search}%")
            ->orWhere('domain', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.companies.index', compact('companies'));
    }
}
