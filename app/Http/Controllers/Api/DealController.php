<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DealController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $user = auth()->user();

        // Sales Team users only see their own deals
        $deals = Deal::query()
            ->visibleTo($user)
            ->with(['contacts', 'companies', 'user'])
            ->paginate(25);

        return DealResource::collection($deals);
    }

    public function store(StoreDealRequest $request): DealResource
    {
        $data = $request->validated();

        // Force ownership to authenticated user - prevent user_id injection
        $data['user_id'] = $request->user()->id;

        $deal = Deal::create($data);

        return new DealResource($deal);
    }

    public function show(Deal $deal): DealResource
    {
        $user = auth()->user();

        // Authorization check
        if ($user->isSalesTeam() && $deal->user_id !== $user->id) {
            abort(403, 'You do not have permission to view this deal.');
        }

        $deal->load(['contacts', 'companies', 'user']);

        return new DealResource($deal);
    }

    public function update(UpdateDealRequest $request, Deal $deal): DealResource
    {
        $user = $request->user();

        // Authorization check
        if ($user->isSalesTeam() && $deal->user_id !== $user->id) {
            abort(403, 'You do not have permission to update this deal.');
        }

        $deal->update($request->validated());

        return new DealResource($deal);
    }

    public function destroy(Deal $deal): Response
    {
        $user = auth()->user();

        // Only Admin can delete deals
        if (! $user->isAdmin()) {
            abort(403, 'Only administrators can delete deals.');
        }

        $deal->delete();

        return response()->noContent();
    }
}
