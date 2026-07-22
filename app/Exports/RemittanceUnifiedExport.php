<?php

namespace App\Exports;

use App\Exports\Sheets\CidSummarySheet;
use App\Exports\Sheets\CompanySummarySheet;
use App\Exports\Sheets\WorkerSummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RemittanceUnifiedExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $billerByOwner,
        private readonly array $workersByCompany,
        private readonly array $workerRows,
    ) {}

    public function sheets(): array
    {
        // Flatten worker breakdowns into flat rows with CID + Company
        $workerSummary = [];

        foreach ($this->workerRows as $group) {
            foreach ($group['rows'] as $row) {
                $workerSummary[] = [
                    'worker_name' => $group['worker_name'],
                    'cid' => $row['cid'] ?? '—',
                    'company' => $row['agency'] ?? '—',
                    'tsv' => $row['tsv'] ?? 0,
                ];
            }
        }

        // Sort by worker name
        usort($workerSummary, fn ($a, $b) => strcmp($a['worker_name'], $b['worker_name']));

        return [
            new CidSummarySheet($this->billerByOwner),
            new CompanySummarySheet($this->workersByCompany),
            new WorkerSummarySheet($workerSummary),
        ];
    }
}
