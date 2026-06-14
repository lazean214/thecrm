<?php

namespace App\Http\Controllers;

use App\Models\GdprExportRequest;
use App\Models\GdprSetting;
use App\Services\GdprRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class GdprAdminController extends Controller
{
    protected $retentionService;

    // Remove the __construct completely
    public function __construct(GdprRetentionService $retentionService)
    {
        $this->retentionService = $retentionService;
        // DO NOT call $this->middleware() here
    }

    public function dashboard()
    {
        $stats = $this->retentionService->getStatistics();
        $recentExports = GdprExportRequest::with('user')
            ->latest()
            ->take(10)
            ->get();
        $settings = GdprSetting::all();

        return view('admin.gdpr.dashboard', compact('stats', 'recentExports', 'settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.entity_type' => 'required|string',
            'settings.*.retention_months' => 'required|integer|min:1|max:120',
            'settings.*.is_enabled' => 'boolean',
            'settings.*.custom_action' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $settingData) {
            GdprSetting::updateOrCreate(
                ['entity_type' => $settingData['entity_type']],
                [
                    'retention_months' => $settingData['retention_months'],
                    'is_enabled' => $settingData['is_enabled'] ?? false,
                    'custom_action' => $settingData['custom_action'] ?? 'anonymize',
                ]
            );
        }

        return redirect()->back()->with('success', 'GDPR retention settings updated successfully.');
    }

    public function runRetentionNow()
    {
        Artisan::call('gdpr:anonymize-expired');

        $output = Artisan::output();

        return redirect()->back()->with('success', 'Retention job executed. '.$output);
    }

    public function exportSettings()
    {
        $settings = GdprSetting::all();

        return response()->json($settings, 200, [
            'Content-Disposition' => 'attachment; filename="gdpr-settings-backup.json"',
        ]);
    }

    public function importSettings(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:1024',
        ]);

        $content = json_decode($request->file('settings_file')->get(), true);

        foreach ($content as $setting) {
            GdprSetting::updateOrCreate(
                ['entity_type' => $setting['entity_type']],
                $setting
            );
        }

        return redirect()->back()->with('success', 'GDPR settings imported successfully.');
    }
}
