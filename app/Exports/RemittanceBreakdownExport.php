<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemittanceBreakdownExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $breakdowns,
    ) {}

    public function collection()
    {
        $rows = [];

        foreach ($this->breakdowns as $group) {
            foreach ($group['rows'] as $row) {
                $rows[] = array_merge($row, [
                    'worker_name' => $group['worker_name'],
                ]);
            }
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return ['Worker Name', 'WK #', 'Agency', 'CID', 'Shift Date', 'WE Date', 'Hours', 'Rate (£)', 'TSV (£)', 'Margin (£)', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row['worker_name'] ?? '',
            $row['week_no'] ?? '',
            $row['agency'] ?? '',
            $row['cid'] ?? '',
            $row['shift_date'] ?? '',
            $row['we_date'] ?? '',
            $row['hours'] ?? 0,
            $row['rate'] ?? 0,
            $row['tsv'] ?? 0,
            $row['margin'] ?? 0,
            $row['status'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
