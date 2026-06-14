<?php

namespace App\Http\Controllers;

use App\Models\SignableEnvelope;
use Illuminate\Http\Request;

class SignableEnvelopeController extends Controller
{
    public function handle(Request $request)
    {
        // Signable webhooks send data via standard urlencoded or json fields
        $fingerprint = $request->input('envelope_fingerprint');
        $action = $request->input('action'); // e.g., 'signed-envelope-complete', 'cancelled-envelope'

        // Locate the tracked entity in your local DB
        $envelope = SignableEnvelope::where('envelope_fingerprint', $fingerprint)->first();

        if (! $envelope) {
            return response()->json(['message' => 'Tracking envelope not found'], 404);
        }

        // Map incoming actions to internal statuses
        switch ($action) {
            case 'signed-envelope-complete':
                $envelope->status = 'signed';
                $envelope->download_url = $request->input('envelope_download');
                $envelope->completed_at = now();

                // Optional: Fire logic to auto-advance Deal pipeline status
                $envelope->deal->update(['status' => 'Contract Signed']);
                break;

            case 'cancelled-envelope':
                $envelope->status = 'cancelled';
                break;

            case 'rejected-envelope':
                $envelope->status = 'rejected';
                break;
        }

        $envelope->save();

        return response()->json(['status' => 'success']);
    }
}
