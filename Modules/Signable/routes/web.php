<?php

use Illuminate\Support\Facades\Route;
use Modules\Signable\App\Http\Controllers\SendEnvelopeController;

Route::middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::view('/envelopes', 'signable::envelopes.index')->name('envelopes.index');
    Route::get('/envelopes/send', SendEnvelopeController::class)->name('envelopes.send');
});
