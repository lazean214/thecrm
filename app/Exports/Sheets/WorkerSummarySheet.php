<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkerSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly array $rows,
    ) {}

    public function title(): string
    {
        return 'Worker Summary';
    }

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return ['Worker Name', 'CID (Deal Owner)', 'Company', 'TSV (£)'];
    }

    public function map($row): array
    {
        return [
            $row['worker_name'] ?? '—',
            $row['cid'] ?? '—',
            $row['company'] ?? '—',
            $row['tsv'] ?? 0,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->rows) + 1;

        return [
            1 => ['font' => ['bold' => true]],
            $lastRow => ['font' => ['bold' => true]],
        ];
    }
}
