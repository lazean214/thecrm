<?php

namespace App\Livewire\Settings;

use App\Models\BusinessSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FiscalYearSettings extends Component
{
    public int $startMonth = 4;

    public int $startDay = 6;

    public int $endMonth = 4;

    public int $endDay = 5;

    public string $previewLabel = '';

    public string $previewStart = '';

    public string $previewEnd = '';

    public array $weekMapping = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $this->startMonth = (int) BusinessSetting::get('fiscal_year_start_month', 4);
        $this->startDay = (int) BusinessSetting::get('fiscal_year_start_day', 6);
        $this->endMonth = (int) BusinessSetting::get('fiscal_year_end_month', 4);
        $this->endDay = (int) BusinessSetting::get('fiscal_year_end_day', 5);

        $this->updatePreview();
    }

    public function updatedStartMonth(): void
    {
        $this->updatePreview();
    }

    public function updatedStartDay(): void
    {
        $this->updatePreview();
    }

    public function updatedEndMonth(): void
    {
        $this->updatePreview();
    }

    public function updatedEndDay(): void
    {
        $this->updatePreview();
    }

    public function save(): void
    {
        BusinessSetting::set('fiscal_year_start_month', $this->startMonth);
        BusinessSetting::set('fiscal_year_start_day', $this->startDay);
        BusinessSetting::set('fiscal_year_end_month', $this->endMonth);
        BusinessSetting::set('fiscal_year_end_day', $this->endDay);

        $this->updatePreview();

        $this->dispatch('notify', type: 'success', message: 'Fiscal year settings saved.');
    }

    private function updatePreview(): void
    {
        $now = Carbon::now();
        $fy = $this->resolveFiscalYear($now);

        $this->previewLabel = $fy['label'];
        $this->previewStart = $fy['start']->format('d M Y');
        $this->previewEnd = $fy['end']->format('d M Y');

        $this->generateWeekMapping($fy['start'], $fy['end']);
    }

    private function generateWeekMapping(Carbon $fyStart, Carbon $fyEnd): void
    {
        $this->weekMapping = [];

        $weekStart = $fyStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekNumber = 1;

        while ($weekStart->lte($fyEnd)) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $this->weekMapping[] = [
                'week' => $weekNumber,
                'start' => $weekStart->format('d M Y'),
                'end' => $weekEnd->format('d M Y'),
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
            ];

            $weekStart->addWeek();
            $weekNumber++;
        }
    }

    private function resolveFiscalYear(Carbon $now): array
    {
        $fyStart = Carbon::create($now->year, $this->startMonth, $this->startDay)->startOfDay();
        $fyEnd = Carbon::create($now->year + 1, $this->endMonth, $this->endDay)->endOfDay();

        if ($now->lt($fyStart)) {
            $fyStart = $fyStart->subYear();
            $fyEnd = $fyEnd->subYear();
        }

        $label = $fyStart->year.'/'.$fyEnd->year;

        return [
            'start' => $fyStart,
            'end' => $fyEnd,
            'label' => $label,
        ];
    }

    public function render()
    {
        return view('livewire.settings.fiscal-year-settings');
    }
}
