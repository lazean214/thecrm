<?php

namespace Modules\Signable\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Signable\App\Services\Signable\SignableClient;
use Throwable;

class SignableTemplateController extends Controller
{
    public function __construct(private readonly SignableClient $client) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $response = $this->client->listTemplates($request->only(['offset', 'limit']));
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function show(string $fingerprint): JsonResponse
    {
        try {
            $response = $this->client->getTemplate($fingerprint);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function destroy(string $fingerprint): JsonResponse
    {
        try {
            $response = $this->client->deleteTemplate($fingerprint);
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
