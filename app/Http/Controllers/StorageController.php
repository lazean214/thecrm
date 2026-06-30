<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    /**
     * Serve files from storage/app/public directory.
     * This replaces the need for `php artisan storage:link` on shared hosting.
     */
    public function serve(Request $request, string $path)
    {
        // Sanitize path to prevent directory traversal
        $path = ltrim(str_replace('..', '', $path), '/');

        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
        ]);
    }
}
