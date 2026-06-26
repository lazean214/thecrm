<?php

use Illuminate\Support\Facades\Route;
use Modules\Signable\App\Http\Controllers\Api\SignableBrandingController;
use Modules\Signable\App\Http\Controllers\Api\SignableContactController;
use Modules\Signable\App\Http\Controllers\Api\SignableEnvelopeController;
use Modules\Signable\App\Http\Controllers\Api\SignableSettingsController;
use Modules\Signable\App\Http\Controllers\Api\SignableTemplateController;
use Modules\Signable\App\Http\Controllers\Api\SignableUserController;
use Modules\Signable\App\Http\Controllers\Api\SignableWebhookController;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/signable')->group(function (): void {
    // Envelopes
    Route::get('envelopes', [SignableEnvelopeController::class, 'index']);
    Route::post('envelopes', [SignableEnvelopeController::class, 'store']);
    Route::post('envelopes/batch-download', [SignableEnvelopeController::class, 'batchDownload']);
    Route::get('envelopes/{fingerprint}', [SignableEnvelopeController::class, 'show']);
    Route::delete('envelopes/{fingerprint}', [SignableEnvelopeController::class, 'destroy']);
    Route::put('envelopes/{fingerprint}/remind', [SignableEnvelopeController::class, 'remind']);
    Route::put('envelopes/{fingerprint}/cancel', [SignableEnvelopeController::class, 'cancel']);
    Route::put('envelopes/{fingerprint}/expire', [SignableEnvelopeController::class, 'expire']);
    Route::patch('envelopes/{fingerprint}/parties/{partyId}', [SignableEnvelopeController::class, 'updateParty']);
    Route::get('deals/{deal}/envelopes', [SignableEnvelopeController::class, 'dealEnvelopes']);
    // BC: template-specific send endpoint
    Route::post('envelopes/template', [SignableEnvelopeController::class, 'storeFromTemplate']);

    // Templates
    Route::get('templates', [SignableTemplateController::class, 'index']);
    Route::get('templates/{fingerprint}', [SignableTemplateController::class, 'show']);
    Route::delete('templates/{fingerprint}', [SignableTemplateController::class, 'destroy']);

    // Contacts
    Route::get('contacts', [SignableContactController::class, 'index']);
    Route::post('contacts', [SignableContactController::class, 'store']);
    Route::get('contacts/{contactId}', [SignableContactController::class, 'show']);
    Route::put('contacts/{contactId}', [SignableContactController::class, 'update']);
    Route::delete('contacts/{contactId}', [SignableContactController::class, 'destroy']);
    Route::get('contacts/{contactId}/envelopes', [SignableContactController::class, 'envelopes']);

    // Users
    Route::get('users', [SignableUserController::class, 'index']);
    Route::post('users', [SignableUserController::class, 'store']);
    Route::get('users/{userId}', [SignableUserController::class, 'show']);
    Route::put('users/{userId}', [SignableUserController::class, 'update']);
    Route::delete('users/{userId}', [SignableUserController::class, 'destroy']);

    // Webhooks
    Route::get('webhooks', [SignableWebhookController::class, 'index']);
    Route::post('webhooks', [SignableWebhookController::class, 'store']);
    Route::get('webhooks/{webhookId}', [SignableWebhookController::class, 'show']);
    Route::put('webhooks/{webhookId}', [SignableWebhookController::class, 'update']);
    Route::delete('webhooks/{webhookId}', [SignableWebhookController::class, 'destroy']);

    // Branding
    Route::get('branding', [SignableBrandingController::class, 'show']);
    Route::put('branding', [SignableBrandingController::class, 'update']);
    Route::get('branding/emails', [SignableBrandingController::class, 'emails']);
    Route::put('branding/emails/{type}', [SignableBrandingController::class, 'updateEmail']);

    // Settings
    Route::get('settings', [SignableSettingsController::class, 'show']);
    Route::put('settings', [SignableSettingsController::class, 'update']);
});
