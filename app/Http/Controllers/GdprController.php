<?php

namespace App\Http\Controllers;

use App\Services\GdprExportService;
use Illuminate\Http\Request;

class GdprController extends Controller
{
    protected $exportService;

    public function __construct(GdprExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function showExportForm()
    {
        return view('gdpr.export');
    }

    public function requestExport(Request $request)
    {
        $user = $request->user();
        $token = $this->exportService->storeExportAndGetToken($user);

        // Store token in session or send by email
        $request->session()->flash('export_token', $token);

        return redirect()->back()->with('status', 'Your data export is ready. Download link valid for 7 days.');
    }

    public function downloadExport(Request $request, $token)
    {
        $data = $this->exportService->retrieveExportByToken($token);

        if (! $data) {
            abort(404, 'Export not found or expired.');
        }

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="gdpr-data.json"',
        ]);
    }
}
