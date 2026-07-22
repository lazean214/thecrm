<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemittanceSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $rows,
    ) {}

    public function collection()
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return ['WK #', 'Worker Name', 'Agency', 'CID', 'TSV (£)'];
    }

    public function map($row): array
    {
        return [
            $row['week_no'] ?? '',
            $row['worker_name'] ?? '',
            $row['agency'] ?? '',
            $row['cid'] ?? '',
            $row['tsv_sum'] ?? 0,
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
