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
        return DealResource::collection(Deal::paginate(25));
    }

    public function store(StoreDealRequest $request): DealResource
    {
        $data = $request->validated();

        if (! isset($data['user_id'])) {
            $data['user_id'] = $request->user()?->id;
        }

        $deal = Deal::create($data);

        return new DealResource($deal);
    }

    public function show(Deal $deal): DealResource
    {
        $deal->load(['contacts', 'companies', 'user']);

        return new DealResource($deal);
    }

    public function update(UpdateDealRequest $request, Deal $deal): DealResource
    {
        $deal->update($request->validated());

        return new DealResource($deal);
    }

    public function destroy(Deal $deal): Response
    {
        $deal->delete();

        return response()->noContent();
    }
}
