<?php

namespace Modules\Signable\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Modules\Signable\App\Http\Requests\UpdateSettingsRequest;
use Modules\Signable\App\Services\Signable\SignableClient;
use Throwable;

class SignableSettingsController extends Controller
{
    public function __construct(private readonly SignableClient $client) {}

    public function show(): JsonResponse
    {
        try {
            $response = $this->client->getSettings();
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        try {
            $response = $this->client->updateSettings($request->validated());
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    private function proxy(ClientResponse $response, int $successStatus = 200): JsonResponse
    {
        if ($response->failed()) {
            return response()->json([
                'message' => 'Signable API returned an error.',
                'status' => $response->status(),
                'error' => $response->json() ?? ['raw' => $response->body()],
            ], $response->status());
        }

        return response()->json(
            $response->json() ?? ['raw' => $response->body()],
            $successStatus
        );
    }

    private function serviceError(string $message): JsonResponse
    {
        return response()->json([
            'message' => 'Unable to call Signable API.',
            'error' => $message,
        ], 500);
    }
}
