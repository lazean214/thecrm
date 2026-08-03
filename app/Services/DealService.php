<?php

namespace App\Services;

use App\Enums\DealStage;
use App\Events\DealStageChanged;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DealService
{
    /**
     * Create a new deal with associated contact and company.
     *
     * @param  array<string, mixed>  $data
     */
    public function createDeal(array $data, User $user): Deal
    {
        return DB::transaction(function () use ($data, $user) {
            $contact = Contact::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]
            );

            $contact->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            $company = Company::firstOrCreate(['name' => $data['consultant_name']]);

            $deal = Deal::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'amount' => $data['amount'] ?? null,
                'stage' => $data['stage'] ?? DealStage::DOC_SENT->value,
                'recruitment_agency' => $data['recruitment_agency'] ?? 'Inbound',
                'consultant_name' => $data['consultant_name'],
                'agency_deal_value' => $data['agency_deal_value'] ?? null,
                'margin_agreed' => $data['margin_agreed'] ?? null,
            ]);

            $contact->companies()->syncWithoutDetaching([$company->id]);
            $contact->deals()->syncWithoutDetaching([$deal->id]);
            $company->deals()->syncWithoutDetaching([$deal->id]);

            return $deal;
        });
    }

    /**
     * Transition a deal to a new stage with authorization checks.
     */
    public function transitionStage(Deal $deal, string $newStage, User $user): bool
    {
        $oldStage = (string) $deal->stage;

        if ($oldStage === $newStage) {
            return false;
        }

        if ($user->isSalesTeam() && (int) $deal->user_id !== $user->id) {
            return false;
        }

        if (! $user->canMoveToStage($newStage)) {
            return false;
        }

        $deal->stage = $newStage;
        $deal->save();

        $reason = $user->isSalesTeam() ? 'Sales Team action' : ($user->isComplianceTeam() ? 'Compliance Team action' : 'System action');
        $deal->logStageChange($oldStage, $newStage, $reason);

        event(new DealStageChanged($deal, $oldStage, $newStage));

        return true;
    }

    /**
     * Update deal and associated contact data.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDeal(Deal $deal, array $data, User $user): bool
    {
        if ($user->isSalesTeam() && (int) $deal->user_id !== $user->id) {
            return false;
        }

        $originalDeal = $deal->replicate();
        $originalStage = (string) $deal->stage;

        $deal->update([
            'name' => $data['name'],
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'hours' => $data['hours'] ?? null,
            'rate' => $data['rate'] ?? null,
            'stage' => $data['stage'],
            'agency_deal_value' => $data['agency_deal_value'] ?? null,
            'margin_agreed' => $data['margin_agreed'] ?? null,
            'recruitment_agency' => $data['recruitment_agency'] ?? null,
            'consultant_name' => $data['consultant_name'] ?? null,
            'date_sent' => $data['date_sent'] ?? null,
            'date_signed' => $data['date_signed'] ?? null,
            'who_signed' => $data['who_signed'] ?? null,
            'signed_doc' => $data['signed_doc'] ?? null,
            'right_to_work' => $data['right_to_work'] ?? null,
            'proof_of_address' => $data['proof_of_address'] ?? null,
            'photo_id_passport' => $data['photo_id_passport'] ?? null,
            'date_set_up' => $data['date_set_up'] ?? null,
            'remittance_received' => $data['remittance_received'] ?? null,
            'date_logged' => $data['date_logged'] ?? null,
            'starter_checklist_recieved_date' => $data['starter_checklist_recieved_date'] ?? null,
            'starter_form' => $data['starter_form'] ?? null,
            'tax_code' => $data['tax_code'] ?? null,
            'contract_recieved_date' => $data['contract_recieved_date'] ?? null,
        ]);

        $deal->logChanges($originalDeal);

        if ($originalStage !== (string) $deal->stage) {
            $reason = $user->isSalesTeam() ? 'Sales Team action' : ($user->isComplianceTeam() ? 'Compliance Team action' : 'System action');
            $deal->logStageChange($originalStage, (string) $deal->stage, $reason);
        }

        if ($originalDeal->user_id != $data['user_id']) {
            $newOwner = User::find($data['user_id']);
            $oldOwner = User::find($originalDeal->user_id);
            $deal->logOwnerChange($originalDeal->user_id, $data['user_id'], $oldOwner?->name, $newOwner?->name);
        }

        return true;
    }

    /**
     * Update the primary contact for a deal.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateContact(Deal $deal, array $data): void
    {
        $contact = $deal->contacts()->first();
        if (! $contact) {
            return;
        }

        $originalContact = $contact->replicate();

        $contact->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'street_address' => $data['street_address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
            'ni_number' => $data['ni_number'] ?? null,
            'bank' => $data['bank'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'sort_code' => $data['sort_code'] ?? null,
        ]);

        foreach (['first_name', 'last_name', 'email', 'phone'] as $field) {
            if ($contact->$field != $originalContact->$field) {
                $deal->logFieldUpdate("contact_{$field}", $originalContact->$field, $contact->$field, "Contact {$field} changed");
            }
        }
    }

    /**
     * Sync company association when consultant name changes.
     */
    public function syncCompany(Deal $deal, ?string $consultantName): void
    {
        if (empty($consultantName) || $deal->consultant_name === $consultantName) {
            return;
        }

        $company = Company::firstOrCreate(['name' => $consultantName]);
        $deal->companies()->syncWithPivotValues([$company->id], ['is_primary' => true]);
        $deal->logAssociationChange('company', 'updated', $company, "Consultant/Agency changed from \"{$deal->consultant_name}\" to \"{$consultantName}\"");

        $primaryContact = $deal->contacts()->first();
        if ($primaryContact && ! $company->contacts()->where('contacts.id', $primaryContact->id)->exists()) {
            $company->contacts()->attach($primaryContact->id);
        }
    }

    /**
     * Batch update deal owners (Compliance only).
     */
    public function batchUpdateOwner(array $dealIds, int $newOwnerId): int
    {
        return Deal::whereIn('id', $dealIds)->update(['user_id' => $newOwnerId]);
    }

    /**
     * Batch update deal stages with authorization checks.
     *
     * @return array{updated: int, skipped: int}
     */
    public function batchUpdateStage(array $dealIds, string $newStage, User $user): array
    {
        $updated = 0;
        $skipped = 0;

        $deals = Deal::whereIn('id', $dealIds)->get();

        foreach ($deals as $deal) {
            if ($user->isSalesTeam() && (int) $deal->user_id !== $user->id) {
                $skipped++;

                continue;
            }

            if (! $user->canMoveToStage($newStage)) {
                $skipped++;

                continue;
            }

            $oldStage = (string) $deal->stage;
            $deal->stage = $newStage;
            $deal->save();

            $reason = $user->isSalesTeam() ? 'Sales Team action' : 'Compliance Team action';
            $deal->logStageChange($oldStage, $newStage, $reason);
            event(new DealStageChanged($deal, $oldStage, $newStage));
            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Batch delete deals (Compliance only).
     */
    public function batchDelete(array $dealIds): int
    {
        return Deal::whereIn('id', $dealIds)->delete();
    }

    /**
     * Get all allowed stages for a user.
     *
     * @return array<int, string>
     */
    public function getAllowedStages(User $user): array
    {
        return $user->getAllowedDealStages();
    }

    /**
     * Check if a user can transition to a specific stage.
     */
    public function canTransitionTo(User $user, string $stage): bool
    {
        return $user->canMoveToStage($stage);
    }
}
