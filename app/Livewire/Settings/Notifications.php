<?php

namespace App\Livewire\Settings;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Notification preferences')]
class Notifications extends Component
{
    public array $preferences = [];

    public function mount(): void
    {
        $this->preferences = Auth::user()->notification_preferences ?? [];
    }

    public function save(): void
    {
        $user = Auth::user();
        $user->notification_preferences = $this->preferences;
        $user->save();

        Flux::toast(variant: 'success', text: 'Notification preferences updated.');
    }

    #[Computed]
    public function events(): array
    {
        return [
            'deal_created' => 'Deal created',
            'deal_commented' => 'Deal commented',
            'deal_inactive' => 'Deal inactive',
            'deal_paid' => 'Deal paid',
            'deal_ready_for_payment' => 'Deal ready for payment',
            'deal_stage_stale' => 'Deal stage stale',
        ];
    }
}
