<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        // Only Admin can list all users
        $user = auth()->user();

        if (! $user->isAdmin()) {
            abort(403, 'Only administrators can list users.');
        }

        return UserResource::collection(User::with(['teams', 'deals'])->paginate(25));
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $user = $request->user();

        // Only Admin can create users
        if (! $user->isAdmin()) {
            abort(403, 'Only administrators can create users.');
        }

        $newUser = User::create($request->validated());

        return new UserResource($newUser);
    }

    public function show(User $user): UserResource
    {
        $authUser = auth()->user();

        // Users can view their own profile, Admins can view all
        if ($authUser->id !== $user->id && ! $authUser->isAdmin()) {
            abort(403, 'You do not have permission to view this user.');
        }

        $user->load(['teams', 'deals']);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $authUser = $request->user();

        // Users can update their own profile (limited fields)
        if ($authUser->id === $user->id) {
            $user->update($request->validated());

            return new UserResource($user);
        }

        // Only Admin can update other users
        if (! $authUser->isAdmin()) {
            abort(403, 'You do not have permission to update this user.');
        }

        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user): Response
    {
        $authUser = auth()->user();

        // Users cannot delete themselves
        if ($authUser->id === $user->id) {
            abort(403, 'You cannot delete your own account.');
        }

        // Only Admin can delete users
        if (! $authUser->isAdmin()) {
            abort(403, 'Only administrators can delete users.');
        }

        $user->delete();

        return response()->noContent();
    }
}
