<?php

namespace App\Http\Controllers;

use App\Exports\DealsExport;
use App\Http\Requests\DealExportRequest;
use Maatwebsite\Excel\Facades\Excel;

class DealExportController extends Controller
{
    public function export(DealExportRequest $request)
    {
        $filters = $request->only([
            'filterDealName',
            'filterOwner',
            'filterContact',
            'filterCompanyName',
            'filterStage',
            'minAmount',
            'maxAmount',
            'dateFrom',
            'dateTo',
        ]);

        return Excel::download(new DealsExport($filters), 'deals-'.now()->format('Y-m-d').'.xlsx');
    }
}
