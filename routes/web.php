<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DealExportController;
use App\Http\Controllers\GdprAdminController;
use App\Http\Controllers\GdprController;
use App\Http\Controllers\RemittanceController;
use App\Http\Controllers\SignableEnvelopeController;
use App\Http\Controllers\StorageController;
use App\Imports\CompanyImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::view('/', 'welcome')->name('home');

// Serve files from storage without needing storage:link (shared hosting compatible)
Route::get('storage/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('help', 'help')->name('help');
    Route::view('reports', 'reports')->name('reports');
    Route::view('deals', 'deals.deals')->name('deals');
    Route::get('deals/export', [DealExportController::class, 'export'])->name('deals.export');
    Route::get('deals/{deal}', [DealController::class, 'show'])->name('deals.show');
    Route::view('contacts', 'contacts')->name('contacts');
    Route::view('contacts/{contact}', 'contacts.show')->name('contacts.show');
    Route::view('companies', 'companies')->name('companies');
    Route::view('companies/{company}', 'companies.show')->name('companies.show');
    Route::view('designer', 'email.index')->name('designer');
    Route::view('designer/create', 'email.create')->name('designer.create');
    Route::view('designer/{email}', 'email.edit')->name('designer.edit');
    Route::view('teams', 'team')->name('teams');
    Route::view('users', 'user')->name('users');
    Route::view('roles', 'roles')->name('roles');
    Route::view('permissions', 'permissions')->name('permissions');
    Route::get('remittances', [RemittanceController::class, 'index'])->name('remittances.index');
    Route::view('remittances/report', 'remittances.report')->name('remittances.report');
    Route::post('/ai/assistant', AssistantController::class)->middleware('throttle:30,1')->name('ai.assistant');
});

require __DIR__.'/settings.php';

require __DIR__.'/simulation.php';

// Signable Webhook - verify signature before processing
Route::post('/api/webhooks/signable', [SignableEnvelopeController::class, 'handle'])
    ->middleware('signable.webhook')
    ->name('webhooks.signable');

// Company Import
Route::post('/import-companies', function (Request $request) {
    $request->validate(['file' => 'required|file|mimes:csv,xlsx,xls|max:10240']);
    try {
        Excel::import(
            new CompanyImport,
            $request->file('file')
        );

        return back()->with('success', '✅ Companies imported!');
    } catch (Exception $e) {
        return back()->with('error', '❌ '.$e->getMessage());
    }
})->middleware('auth')->name('import.companies');

// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/gdpr/export', [GdprController::class, 'showExportForm'])->name('gdpr.export.form');
    Route::post('/gdpr/export', [GdprController::class, 'requestExport'])->name('gdpr.export.request');
    Route::get('/gdpr/download/{token}', [GdprController::class, 'downloadExport'])->name('gdpr.export.download');

    // Admin GDPR routes (add permission middleware)
    Route::prefix('admin/gdpr')->name('admin.gdpr.')->middleware(['can:manage-gdpr'])->group(function () {
        Route::get('/', [GdprAdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/settings', [GdprAdminController::class, 'updateSettings'])->name('update-settings');
        Route::post('/run', [GdprAdminController::class, 'runRetentionNow'])->name('run');
        Route::get('/export-settings', [GdprAdminController::class, 'exportSettings'])->name('export-settings');
        Route::post('/import-settings', [GdprAdminController::class, 'importSettings'])->name('import-settings');
    });
});
