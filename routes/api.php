<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\Deals\KanbanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::apiResource('contacts', ContactController::class)->names('api.contacts');
    Route::apiResource('deals', DealController::class)->names('api.deals');
    Route::apiResource('users', UserController::class)->names('api.users');

    // Lightweight Kanban API
    Route::prefix('deals/kanban')->group(function (): void {
        Route::get('/', [KanbanController::class, 'index'])->name('api.deals.kanban.index');
        Route::patch('/{deal}/stage', [KanbanController::class, 'updateStage'])->name('api.deals.kanban.update-stage');
    });
});
