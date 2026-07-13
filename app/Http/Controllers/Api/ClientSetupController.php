<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientSetupController extends Controller
{
    /**
     * One-time setup endpoint to generate API tokens for external systems.
     * Requires a valid setup key configured in environment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setup_key' => ['required', 'string'],
            'client_name' => ['required', 'string', 'max:255'],
        ]);

        // Validate the setup key against environment (can only be used once)
        $expectedKey = config('app.api_setup_key');

        if (! $expectedKey || ! hash_equals($expectedKey, $validated['setup_key'])) {
            throw ValidationException::withMessages([
                'setup_key' => ['Invalid or expired setup key.'],
            ]);
        }

        // Create a dedicated user for this external client
        $user = User::create([
            'name' => $validated['client_name'],
            'email' => Str::slug($validated['client_name']).'-api@external.local',
            'password' => Hash::make(Str::random(32)), // Random password, never used
        ]);

        // Assign admin role for full access (adjust as needed)
        $user->assignRole('admin');

        // Generate token with descriptive name
        $token = $user->createToken(
            name: 'External API Client',
            abilities: ['*'], // Full abilities - restrict as needed
        )->plainTextToken;

        // Clear the setup key for one-time use (optional security)
        // config(['app.api_setup_key' => null]);

        return response()->json([
            'token' => $token,
            'user_id' => $user->id,
            'message' => 'Save this token securely. It will not be shown again.',
        ], 201);
    }
}
