<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\GdprExportRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GdprExportService
{
    public function exportAllUserData(User $user): array
    {
        $data = [
            'user_profile' => $user->only(['id', 'name', 'email', 'created_at']),
            'contacts' => $this->getContactsData($user),
            'companies' => $this->getCompaniesData($user),
            'deals' => $this->getDealsData($user),
            'export_generated_at' => now()->toIso8601String(),
        ];

        return $data;
    }

    protected function getContactsData(User $user): array
    {
        // If user is sales team, they see only their contacts
        if ($user->isSalesTeam()) {
            $dealIds = $user->deals()->pluck('id');

            return Contact::whereHas('deals', fn ($q) => $q->whereIn('deals.id', $dealIds))
                ->get()
                ->map(fn ($c) => $this->sanitizeContact($c))
                ->toArray();
        }

        // Compliance / admin see all contacts (but still GDPR filtered)
        return Contact::whereNull('anonymised_at')
            ->get()
            ->map(fn ($c) => $this->sanitizeContact($c))
            ->toArray();
    }

    protected function sanitizeContact(Contact $contact): array
    {
        return $contact->only([
            'id', 'first_name', 'last_name', 'email', 'phone',
            'street_address', 'city', 'state', 'postal_code', 'country',
            'date_of_birth', 'marital_status', 'gender', 'created_at',
        ]);
    }

    protected function getCompaniesData(User $user): array
    {
        if ($user->isSalesTeam()) {
            $dealIds = $user->deals()->pluck('id');

            return Company::whereHas('deals', fn ($q) => $q->whereIn('deals.id', $dealIds))
                ->get()
                ->toArray();
        }

        return Company::all()->toArray();
    }

    protected function getDealsData(User $user): array
    {
        $query = Deal::with(['contacts', 'companies']);
        if ($user->isSalesTeam()) {
            $query->where('user_id', $user->id);
        }

        return $query->get()->map(fn ($deal) => [
            'id' => $deal->id,
            'name' => $deal->name,
            'amount' => $deal->amount,
            'stage' => (string) $deal->stage,
            'created_at' => $deal->created_at,
            'contacts' => $deal->contacts->map(fn ($c) => $c->only(['id', 'first_name', 'last_name', 'email'])),
            'companies' => $deal->companies->map(fn ($comp) => $comp->only(['id', 'name', 'email'])),
        ])->toArray();
    }

    public function storeExportAndGetToken(User $user): string
    {
        $data = $this->exportAllUserData($user);
        $fileName = 'gdpr_export_'.$user->id.'_'.now()->format('Ymd_His').'.json';
        $filePath = 'gdpr_exports/'.$fileName;

        Storage::disk('local')->put($filePath, json_encode($data, JSON_PRETTY_PRINT));

        $token = Str::random(64);
        GdprExportRequest::create([
            'user_id' => $user->id,
            'download_token' => hash('sha256', $token),
            'file_path' => $filePath,
            'exported_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        return $token;
    }

    public function retrieveExportByToken(string $plainToken): ?array
    {
        $hashed = hash('sha256', $plainToken);
        $request = GdprExportRequest::where('download_token', $hashed)
            ->where('expires_at', '>', now())
            ->first();

        if (! $request || ! Storage::disk('local')->exists($request->file_path)) {
            return null;
        }

        $content = Storage::disk('local')->get($request->file_path);

        return json_decode($content, true);
    }
}
