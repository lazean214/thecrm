<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Ai\AiCrmAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, AiCrmAssistant $assistant): JsonResponse
    {
        $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $result = $assistant->ask($request->string('question')->toString(), $request->user());

        return response()->json($result);
    }
}
