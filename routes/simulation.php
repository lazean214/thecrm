<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

// Simulation helper endpoints - only available in local/testing environments
if (! app()->environment('production')) {
    Route::prefix('simulation')->group(function () {

        Route::get('health', function () {
            return response()->json([
                'status' => 'ok',
                'time' => now()->toIso8601String(),
                'env' => app()->environment(),
            ]);
        });

        Route::post('seed', function (Request $request) {
            $count = min((int) $request->input('count', 20), 50);
            $password = Hash::make('password');

            $roles = [
                'sales' => Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']),
                'compliance' => Role::firstOrCreate(['name' => 'compliance', 'guard_name' => 'web']),
                'admin' => Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']),
            ];

            $created = 0;

            for ($i = 1; $i <= $count; $i++) {
                $email = "user{$i}@test.com";
                $existing = User::where('email', $email)->first();
                if ($existing) {
                    continue;
                }

                $persona = match (true) {
                    $i <= $count * 0.4 => 'sales',
                    $i <= $count * 0.7 => 'manager',
                    $i <= $count * 0.9 => 'admin',
                    default => 'guest',
                };

                $user = User::create([
                    'name' => match ($persona) {
                        'sales' => "Sales Rep {$i}",
                        'manager' => "Manager {$i}",
                        'admin' => "Admin {$i}",
                        default => "Guest {$i}",
                    },
                    'email' => $email,
                    'password' => $password,
                    'email_verified_at' => now(),
                ]);

                if ($persona === 'admin') {
                    $user->assignRole($roles['admin']);
                } else {
                    $user->assignRole($roles[$persona] ?? $roles['sales']);
                }

                $created++;
            }

            return response()->json([
                'message' => "Seeded {$created} users",
                'total_users' => User::count(),
            ]);
        });

        Route::post('cleanup', function () {
            $deleted = User::where('email', 'like', 'user%@test.com')->delete();

            return response()->json([
                'message' => "Cleaned up {$deleted} users",
            ]);
        });
    });
}
