<?php

// app/Traits/LogsDealHistory.php

namespace App\Traits;

use App\Enums\DealStage;
use App\Models\DealHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait LogsDealHistory
{
    /**
     * Boot the trait.
     */
    protected static function bootLogsDealHistory()
    {
        static::created(function ($model) {
            $model->logCreation();
        });

        // Remove the updating hook to avoid duplicate logging
        // We'll manually log changes instead
    }

    /**
     * Log deal creation.
     */
    public function logCreation(): void
    {
        $this->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'details' => "Deal \"{$this->name}\" was created",
            'metadata' => [
                'name' => $this->name,
                'amount' => $this->amount,
                'stage' => $this->stage instanceof DealStage ? $this->stage->value : (string) $this->stage,
            ],
        ]);
    }

    /**
     * Log stage changes.
     */
    public function logStageChange(string $oldStage, string $newStage, ?string $reason = null): void
    {
        $user = auth()->user();
        $userName = $user?->name ?? 'System';

        $this->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'stage_moved',
            'field' => 'stage',
            'old_value' => $oldStage,
            'new_value' => $newStage,
            'details' => "Stage moved from \"{$oldStage}\" to \"{$newStage}\" by {$userName}".($reason ? " ({$reason})" : ''),
            'metadata' => [
                'old_stage' => $oldStage,
                'new_stage' => $newStage,
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * Log owner change.
     */
    public function logOwnerChange(?int $oldOwnerId, ?int $newOwnerId, ?string $oldOwnerName = null, ?string $newOwnerName = null): void
    {
        $user = auth()->user();
        $actorName = $user?->name ?? 'System';

        $oldOwner = $oldOwnerName ?? optional(User::find($oldOwnerId))->name ?? 'None';
        $newOwner = $newOwnerName ?? optional(User::find($newOwnerId))->name ?? 'None';

        $this->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'owner_changed',
            'field' => 'user_id',
            'old_value' => $oldOwnerId,
            'new_value' => $newOwnerId,
            'details' => "Deal owner changed from \"{$oldOwner}\" to \"{$newOwner}\" by {$actorName}",
            'metadata' => [
                'old_owner_id' => $oldOwnerId,
                'new_owner_id' => $newOwnerId,
                'old_owner_name' => $oldOwner,
                'new_owner_name' => $newOwner,
            ],
        ]);
    }

    /**
     * Log association changes (contacts, companies).
     */
    public function logAssociationChange(string $associationType, string $action, $entity, ?string $details = null): void
    {
        $user = auth()->user();
        $actorName = $user?->name ?? 'System';

        $entityName = $entity->name ?? ($entity->first_name.' '.$entity->last_name) ?? $entity->id;

        $this->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'association_updated',
            'field' => $associationType,
            'details' => $details ?? "{$associationType} {$action}: \"{$entityName}\" by {$actorName}",
            'metadata' => [
                'association_type' => $associationType,
                'association_action' => $action,
                'entity_id' => $entity->id,
                'entity_name' => $entityName,
            ],
        ]);
    }

    /**
     * Log generic field updates.
     */
    public function logFieldUpdate(string $field, $oldValue, $newValue, ?string $customDetails = null): void
    {
        // Skip logging certain fields
        $skipFields = ['updated_at', 'stage_updated_at'];
        if (in_array($field, $skipFields)) {
            return;
        }

        // Format values for display
        $oldDisplay = $this->formatValueForDisplay($field, $oldValue);
        $newDisplay = $this->formatValueForDisplay($field, $newValue);

        // Only log if values are different
        if ($oldDisplay === $newDisplay) {
            return;
        }

        $user = auth()->user();
        $actorName = $user?->name ?? 'System';

        $fieldLabel = $this->getFieldLabel($field);

        $this->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'details_updated',
            'field' => $field,
            'old_value' => is_scalar($oldValue) ? (string) $oldValue : json_encode($oldValue),
            'new_value' => is_scalar($newValue) ? (string) $newValue : json_encode($newValue),
            'details' => $customDetails ?? "{$fieldLabel} changed from \"{$oldDisplay}\" to \"{$newDisplay}\" by {$actorName}",
            'metadata' => [
                'field' => $field,
                'field_label' => $fieldLabel,
            ],
        ]);
    }

    /**
     * Log multiple field updates at once.
     */
    public function logMultipleFieldUpdates(array $changes): void
    {
        foreach ($changes as $field => [$oldValue, $newValue]) {
            $this->logFieldUpdate($field, $oldValue, $newValue);
        }
    }

    /**
     * Detect and log all changes between two model states.
     * This is the fixed method - now expects both original and updated models.
     * Or you can call it with just the original model and it will compare to current.
     */
    public function logChanges($original, ?Model $updated = null): void
    {
        // If only one argument is passed, use current model as updated
        if ($updated === null) {
            $updated = $this;
        }

        $ignoreFields = ['updated_at', 'created_at', 'stage_updated_at'];

        foreach ($updated->getAttributes() as $field => $newValue) {
            if (in_array($field, $ignoreFields)) {
                continue;
            }

            $oldValue = $original->getAttribute($field);

            if ($oldValue != $newValue) {
                // Skip logging stage changes here as they're handled separately
                if ($field !== 'stage') {
                    $this->logFieldUpdate($field, $oldValue, $newValue);
                }
            }
        }
    }

    /**
     * Get human-readable field label.
     */
    protected function getFieldLabel(string $field): string
    {
        $labels = [
            'name' => 'Deal name',
            'amount' => 'Amount',
            'agency_deal_value' => 'Agency deal value',
            'margin_agreed' => 'Margin agreed',
            'recruitment_agency' => 'Recruitment agency',
            'consultant_name' => 'Consultant name',
            'date_sent' => 'Date sent',
            'date_signed' => 'Date signed',
            'who_signed' => 'Who signed',
            'date_set_up' => 'Date set up',
            'remittance_received' => 'Remittance received',
            'date_logged' => 'Date logged',
            'starter_checklist_recieved_date' => 'Starter checklist received date',
            'starter_form' => 'Starter form',
            'tax_code' => 'Tax code',
            'contract_recieved_date' => 'Contract received date',
            'right_to_work' => 'Right to work',
            'proof_of_address' => 'Proof of address',
            'photo_id_passport' => 'Photo ID/Passport',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Format value for display in logs.
     */
    protected function formatValueForDisplay(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return 'Not set';
        }

        if (in_array($field, ['amount', 'agency_deal_value', 'margin_agreed']) && is_numeric($value)) {
            return '£'.number_format((float) $value, 2);
        }

        if (in_array($field, ['date_sent', 'date_signed', 'date_set_up', 'date_logged', 'contract_recieved_date', 'starter_checklist_recieved_date'])) {
            return date('d M Y', strtotime($value));
        }

        return (string) $value;
    }

    /**
     * Get all history records for this deal.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(DealHistory::class)->latest();
    }
}
