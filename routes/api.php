<?php

use App\Http\Controllers\Api\ClientSetupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\Deals\KanbanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// One-time setup endpoint for external systems
Route::post('client/setup', [ClientSetupController::class, 'store'])->name('api.client.setup');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    // Kanban routes MUST be registered before apiResource('deals') to avoid {deal} over-matching
    Route::prefix('deals/kanban')->group(function (): void {
        Route::get('/', [KanbanController::class, 'index'])->name('api.deals.kanban.index');
        Route::patch('/{deal}/stage', [KanbanController::class, 'updateStage'])->name('api.deals.kanban.update-stage');
    });

    Route::apiResource('contacts', ContactController::class)->names('api.contacts');
    Route::apiResource('deals', DealController::class)->names('api.deals');
    Route::apiResource('users', UserController::class)->names('api.users');
});
