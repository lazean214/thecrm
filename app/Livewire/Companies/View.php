<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class View extends Component
{
    public Company $company;

    public bool $showEdit = false;

    public string $name = '';

    public string $email = '';

    public string $domain = '';

    public string $phone = '';

    public string $message = '';

    public Collection $contacts;

    public Collection $deals;

    public function mount(): void
    {
        $this->name = $this->company->name;
        $this->email = $this->company->email ?? '';
        $this->domain = $this->company->domain ?? '';
        $this->phone = $this->company->phone ?? '';

        $this->company->load(['contacts', 'deals.user']);
        $this->contacts = $this->company->contacts;
        $this->deals = $this->company->deals;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'domain' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ];
    }

    public function edit(): void
    {
        $this->showEdit = true;
        $this->message = '';
    }

    public function save(): void
    {
        $this->validate();

        $this->company->update([
            'name' => $this->name,
            'email' => $this->email ?: null,
            'domain' => $this->domain ?: null,
            'phone' => $this->phone ?: null,
        ]);

        $this->showEdit = false;
        $this->message = 'Company updated successfully.';
    }

    public function cancel(): void
    {
        $this->showEdit = false;
        $this->message = '';
    }

    public function delete(): void
    {
        $this->company->delete();
        $this->redirectRoute('companies');
    }

    public function render()
    {
        return view('livewire.companies.view');
    }
}
