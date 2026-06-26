<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $user = auth()->user();

        // Sales Team users only see contacts linked to their deals
        // Admin and non-sales users can see all contacts
        $query = Contact::query()
            ->with(['companies', 'deals']);

        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            $query->whereHas('deals', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return ContactResource::collection($query->paginate(25));
    }

    public function store(StoreContactRequest $request): ContactResource
    {
        $contact = Contact::create($request->validated());

        return new ContactResource($contact);
    }

    public function show(Contact $contact): ContactResource
    {
        $user = auth()->user();

        // Authorization check - Admin can view all
        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            $canView = $contact->deals()
                ->where('user_id', $user->id)
                ->exists();

            if (! $canView) {
                abort(403, 'You do not have permission to view this contact.');
            }
        }

        $contact->load(['companies', 'deals']);

        return new ContactResource($contact);
    }

    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $user = $request->user();

        // Authorization check - Admin can update all
        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            $canUpdate = $contact->deals()
                ->where('user_id', $user->id)
                ->exists();

            if (! $canUpdate) {
                abort(403, 'You do not have permission to update this contact.');
            }
        }

        $contact->update($request->validated());

        return new ContactResource($contact);
    }

    public function destroy(Contact $contact): Response
    {
        $user = auth()->user();

        // Only Admin can delete contacts
        if (! $user->isAdmin()) {
            abort(403, 'Only administrators can delete contacts.');
        }

        $contact->delete();

        return response()->noContent();
    }
}
