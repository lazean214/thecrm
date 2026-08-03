<?php

use Livewire\Component;
use App\Models\Deal;
use App\Models\Contact;
use App\Models\Company;
use App\Models\User;
use App\Enums\DealStage;

new class extends Component
{
    public $isShowingCreateModal = false;

    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $name;
    public $amount;
    public $stage = DealStage::DOC_SENT->value;
    public $agency_deal_value;
    public $margin_agreed;
    public $recruitment_agency = 'Inbound';
    public $consultant_name;
    public $user_id = null;

    // Autocomplete state
    public array $consultantSuggestions = [];
    public bool  $showConsultantDropdown = false;

    public function mount()
    {
        $this->user_id = auth()->id();
    }

    public $existingContact = null;

    public function updatedEmail()
    {
        $this->existingContact = Contact::where('email', $this->email)->first();
        if ($this->existingContact) {
            $this->dispatch('contact-found', contact: $this->existingContact);
        }
    }

    public function useExistingContact()
    {
        if (!$this->existingContact) return;
        $this->first_name = $this->existingContact->first_name;
        $this->last_name  = $this->existingContact->last_name;
        $this->phone      = $this->existingContact->phone;
    }

    // Live search called as user types consultant name
    public function updatedConsultantName()
    {
        $query = trim($this->consultant_name);

        if (strlen($query) < 1) {
            $this->consultantSuggestions = [];
            $this->showConsultantDropdown = false;
            return;
        }

        $this->consultantSuggestions = Company::where('name', 'like', "%{$query}%")
            ->limit(8)
            ->pluck('name')
            ->toArray();

        $this->showConsultantDropdown = count($this->consultantSuggestions) > 0;
    }

    public function selectConsultant(string $name)
    {
        $this->consultant_name        = $name;
        $this->consultantSuggestions  = [];
        $this->showConsultantDropdown = false;
    }

    public function closeConsultantDropdown()
    {
        $this->showConsultantDropdown = false;
    }

    public function save()
    {
        $this->validate([
            'name'            => 'required',
            'email'           => 'required|email',
            'first_name'      => 'required',
            'consultant_name' => 'required',
        ]);

        $contact = Contact::firstOrCreate(
            ['email' => $this->email],
            ['first_name' => $this->first_name, 'last_name' => $this->last_name, 'phone' => $this->phone]
        );

        $contact->update([
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'phone'      => $this->phone,
        ]);

        $company = Company::firstOrCreate(['name' => $this->consultant_name]);

        $deal = Deal::create([
            'user_id'            => $this->user_id,
            'name'               => $this->name,
            'amount'             => $this->amount,
            'stage'              => $this->stage,
            'recruitment_agency' => $this->recruitment_agency,
            'consultant_name'    => $this->consultant_name,
            'agency_deal_value'  => $this->agency_deal_value,
            'margin_agreed'      => $this->margin_agreed,
        ]);

        $contact->companies()->syncWithoutDetaching([$company->id]);
        $contact->deals()->syncWithoutDetaching([$deal->id]);
        $company->deals()->syncWithoutDetaching([$deal->id]);

        $this->reset([
            'first_name', 'last_name', 'email', 'phone', 'name', 'amount',
            'stage', 'recruitment_agency', 'consultant_name', 'agency_deal_value', 'margin_agreed',
        ]);

        $this->isShowingCreateModal = false;
        $this->dispatch('dealCreated');

        return $this->redirect(route('deals.show', $deal), navigate: true);
    }
};
?>

<style>
   
</style>

<div class="deal-modal-wrap">

    <button class="deal-trigger-btn" wire:click="$set('isShowingCreateModal', true)">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        New Deal
    </button>

    @if($isShowingCreateModal)
    {{-- Backdrop: JS handles outside-click so Livewire re-renders don't falsely trigger it --}}
    <div class="deal-backdrop" id="deal-backdrop">
        <div class="deal-modal" id="deal-modal" role="dialog" aria-modal="true" aria-labelledby="deal-modal-title">

            {{-- Header --}}
            <div class="deal-modal-header">
                <div>
                    <h2 id="deal-modal-title">Create New Deal</h2>
                    <p>Fill in the details below to add a deal to your pipeline.</p>
                </div>
                <button type="button" class="deal-close-btn" wire:click="$set('isShowingCreateModal', false)" aria-label="Close">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="deal-modal-body">
                <form wire:submit.prevent="save" id="deal-form">

                    {{-- Deal Info --}}
                    <div class="deal-section">
                        <p class="deal-section-label">Deal Info</p>
                        <div class="deal-field">
                            <label class="deal-label">Deal Name <span>*</span></label>
                            <input wire:model="name" type="text" placeholder="e.g. Senior Dev Contract — Acme" class="deal-input">
                            @error('name') <span class="deal-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="deal-field-grid-2">
                            <div class="deal-field">
                                <label class="deal-label">Timesheet Value</label>
                                <input wire:model="amount" type="number" placeholder="0.00" class="deal-input">
                            </div>
                            <div class="deal-field">
                                <label class="deal-label">Deal Stage</label>
                                <select wire:model="stage" class="deal-select">
                                    @foreach(DealStage::cases() as $stageOption)
                                        <option value="{{ $stageOption->value }}">{{ str_replace('_', ' ', $stageOption->name) }}</option>
                                    @endforeach
                                </select>
                               
                            </div>
                        </div>
                    </div>

                    {{-- Contact --}}
                    <div class="deal-section">
                        <p class="deal-section-label">Contact</p>
                        <div class="deal-field">
                            <label class="deal-label">Email <span>*</span></label>
                            <input wire:model.live="email" type="email" placeholder="contact@email.com" class="deal-input">
                            @error('email') <span class="deal-error">{{ $message }}</span> @enderror
                        </div>

                        @if($existingContact)
                            <div class="deal-contact-banner">
                                <p><strong>Existing contact found:</strong> {{ $existingContact->first_name }} {{ $existingContact->last_name }}</p>
                                <button type="button" class="deal-use-btn" wire:click="useExistingContact">Use Details</button>
                            </div>
                        @endif

                        <div class="deal-field-grid-2" style="margin-top:12px">
                            <div class="deal-field">
                                <label class="deal-label">First Name <span>*</span></label>
                                <input wire:model="first_name" type="text" placeholder="Jane" class="deal-input">
                                @error('first_name') <span class="deal-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="deal-field">
                                <label class="deal-label">Last Name</label>
                                <input wire:model="last_name" type="text" placeholder="Smith" class="deal-input">
                            </div>
                        </div>
                        <div class="deal-field" style="margin-top:12px">
                            <label class="deal-label">Phone</label>
                            <input wire:model="phone" type="tel" placeholder="+44 7700 000000" class="deal-input">
                        </div>
                    </div>

                    {{-- Recruitment --}}
                    <div class="deal-section">
                        <p class="deal-section-label">Recruitment</p>
                        <div class="deal-field">
                            <label class="deal-label">Recruitment Source</label>
                            <select wire:model.live="recruitment_agency" class="deal-select">
                                <option value="" disabled selected>Select source…</option>
                                <option value="Inbound">Inbound</option>
                                <option value="Referral">Referral</option>
                            </select>
                        </div>

                        @if($recruitment_agency === 'Referral')
                            <div class="deal-referral-panel">
                                <div class="deal-field">
                                    <label class="deal-label">Agency Name <span>*</span></label>
                                    <div class="deal-autocomplete-wrap">
                                        <input
                                            wire:model.live="consultant_name"
                                            type="text"
                                            placeholder="Search or enter agency"
                                            class="deal-input"
                                            autocomplete="off"
                                        >
                                        @if($showConsultantDropdown)
                                            <div class="deal-autocomplete-dropdown">
                                                @foreach($consultantSuggestions as $suggestion)
                                                    <div class="deal-autocomplete-item" wire:click="selectConsultant({{ Js::from($suggestion) }})">
                                                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                                                            <circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.3"/>
                                                            <path d="M4.5 6.5l1.5 1.5 2.5-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        {{ $suggestion }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    @error('consultant_name') <span class="deal-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="deal-field-grid-2" style="margin-top:12px">
                                    <div class="deal-field">
                                        <label class="deal-label">Agency Deal Value</label>
                                        <input wire:model="agency_deal_value" type="number" placeholder="0.00" class="deal-input">
                                    </div>
                                    <div class="deal-field">
                                        <label class="deal-label">Margin Agreed</label>
                                        <input wire:model="margin_agreed" type="number" placeholder="0%" class="deal-input">
                                    </div>
                                </div>
                            </div>

                        @elseif($recruitment_agency === 'Inbound')
                            <div class="deal-referral-panel">
                                <div class="deal-field">
                                    <label class="deal-label">Agency Name <span>*</span></label>
                                    <div class="deal-autocomplete-wrap">
                                        <input
                                            wire:model.live="consultant_name"
                                            type="text"
                                            placeholder="Search or enter agency"
                                            class="deal-input"
                                            autocomplete="off"
                                        >
                                        @if($showConsultantDropdown)
                                            <div class="deal-autocomplete-dropdown">
                                                @foreach($consultantSuggestions as $suggestion)
                                                    <div class="deal-autocomplete-item" wire:click="selectConsultant({{ Js::from($suggestion) }})">
                                                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                                                            <circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.3"/>
                                                            <path d="M4.5 6.5l1.5 1.5 2.5-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        {{ $suggestion }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    @error('consultant_name') <span class="deal-error">{{ $message }}</span> @enderror
                                </div>
                                <div class="deal-field-grid-2" style="margin-top:12px">
                                    <div class="deal-field">
                                        <label class="deal-label">Agency Deal Value</label>
                                        <input wire:model="agency_deal_value" type="number" placeholder="0.00" class="deal-input">
                                    </div>
                                    <div class="deal-field">
                                        <label class="deal-label">Margin Agreed</label>
                                        <input wire:model="margin_agreed" type="number" placeholder="0%" class="deal-input">
                                    </div>
                                </div>
                            </div>

                        @else
                            <div class="deal-field">
                                <label class="deal-label">Consultant Name <span>*</span></label>
                                <input wire:model="consultant_name" type="text" placeholder="Consultant's name" class="deal-input">
                                @error('consultant_name') <span class="deal-error">{{ $message }}</span> @enderror
                            </div>
                            
                        @endif
                    </div>

                </form>
            </div>

            {{-- Footer --}}
            <div class="deal-modal-footer">
                <button type="button" class="deal-cancel-btn" wire:click="$set('isShowingCreateModal', false)">
                    Cancel
                </button>
                <button type="submit" form="deal-form" class="deal-submit-btn"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading wire:target="save" class="deal-spinner"></span>
                    <span wire:loading.remove wire:target="save">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none" style="display:inline-block;vertical-align:-2px;margin-right:2px">
                            <path d="M2 6.5l3.5 3.5 5.5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    Save Deal
                </button>
            </div>

        </div>
    </div>

    <script>
        // Close modal only when clicking the backdrop itself — not bubbled events from inside.
        // We use a pointerdown check: record if the press started on the backdrop,
        // then only close on pointerup if it also ends on the backdrop.
        (function () {
            let downOnBackdrop = false;

            document.addEventListener('pointerdown', function (e) {
                const backdrop = document.getElementById('deal-backdrop');
                const modal    = document.getElementById('deal-modal');
                if (!backdrop || !modal) return;
                // True only if the press starts on the backdrop element itself (not children)
                downOnBackdrop = (e.target === backdrop);
            });

            document.addEventListener('pointerup', function (e) {
                const backdrop = document.getElementById('deal-backdrop');
                if (!backdrop) return;
                // Only close if both press and release were on the bare backdrop
                if (downOnBackdrop && e.target === backdrop) {
                    @this.set('isShowingCreateModal', false);
                }
                downOnBackdrop = false;
            });
        })();
    </script>
    @endif

</div>