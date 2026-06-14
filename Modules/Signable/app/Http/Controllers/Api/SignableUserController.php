<?php

namespace Modules\Signable\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Signable\App\Http\Requests\CreateUserRequest;
use Modules\Signable\App\Http\Requests\UpdateUserRequest;
use Modules\Signable\App\Services\Signable\SignableClient;
use Throwable;

class SignableUserController extends Controller
{
    public function __construct(private readonly SignableClient $client) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $response = $this->client->listUsers($request->only(['offset', 'limit']));
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $response = $this->client->createUser($request->validated());
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response, 201);
    }

    public function show(int $userId): JsonResponse
    {
        try {
            $response = $this->client->getUser($userId);
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function update(UpdateUserRequest $request, int $userId): JsonResponse
    {
        try {
            $response = $this->client->updateUser($userId, $request->validated());
        } catch (Throwable $e) {
            return $this->serviceError($e->getMessage());
        }

        return $this->proxy($response);
    }

    public function destroy(int $userId): JsonResponse
    {
        try {
            $response = $this->client->deleteUser($userId);
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
