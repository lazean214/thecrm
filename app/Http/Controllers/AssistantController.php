<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AiAssistantRequest;
use App\Services\Ai\ChatBot\CrmChatBot;
use Illuminate\Http\JsonResponse;

class AssistantController extends Controller
{
    public function __invoke(AiAssistantRequest $request, CrmChatBot $chatBot): JsonResponse
    {
        $reply = $chatBot->ask($request->string('question')->toString(), $request->user());

        return response()->json([
            'tool' => $reply->intent,
            'arguments' => $reply->detailRows,
            'answer' => $reply->answer,
            'detail' => $reply->detail,
            'suggestions' => $reply->suggestions,
            'question' => $reply->question,
            'questionOptions' => $reply->questionOptions,
            'dealsUrl' => $reply->dealsUrl,
        ]);
    }
}
