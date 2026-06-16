<?php

namespace App\Livewire\Settings;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DataManagement extends Component
{
    public int $dealsCount = 0;
    public int $contactsCount = 0;
    public int $companiesCount = 0;

    public function mount(): void
    {
        $this->loadCounts();
    }

    public function loadCounts(): void
    {
        $this->dealsCount = Deal::count();
        $this->contactsCount = Contact::count();
        $this->companiesCount = Company::count();
    }

    public function purgeDeals(): void
    {
        $count = Deal::count();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('contact_deal')->delete();
        DB::table('company_deal')->delete();
        DB::table('deal_signable_envelopes')->delete();
        Deal::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->dispatch('notify', type: 'success', message: "Purged {$count} deals.");
        $this->loadCounts();
    }

    public function purgeContacts(): void
    {
        $count = Contact::count();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('contact_deal')->delete();
        Contact::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->dispatch('notify', type: 'success', message: "Purged {$count} contacts.");
        $this->loadCounts();
    }

    public function purgeCompanies(): void
    {
        $count = Company::count();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('company_deal')->delete();
        Company::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->dispatch('notify', type: 'success', message: "Purged {$count} companies.");
        $this->loadCounts();
    }

    public function render()
    {
        return view('livewire.settings.data-management');
    }
}