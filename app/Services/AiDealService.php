<?php

namespace App\Services;

use App\Models\Deal;
use Illuminate\Support\Facades\Cache;

class AiDealService
{
    public function summary(Deal $deal): string
    {
        return Cache::remember("deal_summary_{$deal->id}", 3600, function () use ($deal) {
            return $this->buildSummary($deal);
        });
    }

    public function actionPrompts(Deal $deal): array
    {
        return Cache::remember("deal_actions_{$deal->id}", 1800, function () use ($deal) {
            return $this->buildActions($deal);
        });
    }

    public function forget(Deal $deal): void
    {
        Cache::forget("deal_summary_{$deal->id}");
        Cache::forget("deal_actions_{$deal->id}");
    }

    private function buildSummary(Deal $deal): string
    {
        $contact = $deal->primaryContact();
        $company = $deal->primaryCompany();
        $stageDays = $deal->stage_updated_at?->diffInDays(now()) ?? 0;

        $contactName = $contact ? "{$contact->first_name} {$contact->last_name}" : 'Unknown Contact';
        $companyName = $company?->name ?? 'Unknown Company';

        $parts = [
            "This £{$deal->amount} deal for {$contactName} ({$companyName}),",
            "managed by {$deal->user?->name},",
            "is currently in the \"{$deal->stage?->value}\" stage",
            "and was last updated {$stageDays} day(s) ago.",
        ];

        $missing = [];
        if (! $deal->right_to_work) {
            $missing[] = 'Right to Work';
        }
        if (! $deal->proof_of_address) {
            $missing[] = 'Proof of Address';
        }
        if (! $deal->photo_id_passport) {
            $missing[] = 'Photo ID/Passport';
        }

        if (count($missing) === 0) {
            $parts[] = 'All compliance documents have been provided.';
        } elseif (count($missing) === 1) {
            $parts[] = "The {$missing[0]} has not yet been provided.";
        } else {
            $last = array_pop($missing);
            $parts[] = 'Key compliance requirements such as '.implode(', ', $missing).' and '.$last.' have not yet been provided.';
        }

        return implode(' ', $parts);
    }

    private function buildActions(Deal $deal): array
    {
        $actions = [];
        $contact = $deal->primaryContact();
        $contactInfo = $contact ? "{$contact->first_name} {$contact->last_name} ({$contact->email})" : 'the client';
        $stage = $deal->stage?->value;

        switch ($stage) {
            case 'doc sent':
                $actions[] = "Follow up with {$contactInfo} to ensure the documents have been signed and returned.";
                $actions[] = 'Confirm the correct documents were sent and check if any additional information is needed.';
                break;

            case 'doc signed':
                $missing = [];
                if (! $deal->right_to_work) {
                    $missing[] = 'Right to Work';
                }
                if (! $deal->proof_of_address) {
                    $missing[] = 'Proof of Address';
                }
                if (! $deal->photo_id_passport) {
                    $missing[] = 'Photo ID/Passport';
                }

                if (count($missing) > 0) {
                    $list = count($missing) === 1 ? $missing[0] : implode(' and ', $missing);
                    $actions[] = "Contact {$contactInfo} to request the missing {$list} compliance documents.";
                }

                $docCount = $deal->getMedia('compliance_documents')->count() + $deal->getMedia('contract_documents')->count();
                if ($docCount === 0) {
                    $actions[] = 'Upload the signed contract to the CRM to resolve the discrepancy of having 0 documents uploaded while in the "doc signed" stage.';
                }

                $actions[] = 'Log an introductory call or email with the client to acknowledge the signed document and outline the next onboarding steps.';
                break;

            case 'compliant':
                $actions[] = 'Confirm all compliance documentation is complete and prepare the deal for the payment stage.';
                if (! $deal->mda_setup) {
                    $actions[] = 'Set up the MDA arrangement for this deal to proceed with payment.';
                }
                break;

            case 'ready for payment':
                $actions[] = "Process the payment of £{$deal->amount} for this deal.";
                $actions[] = 'Confirm remittance details with the client and log the payment date.';
                break;

            case 'paid':
                $actions[] = 'Send a confirmation of payment to the client.';
                $actions[] = 'Follow up with the client to ensure satisfaction and gather feedback.';
                break;

            case 'lost':
                $actions[] = 'Document the reasons for losing this deal for future reference.';
                $actions[] = 'Consider reaching out to the client to understand if there are future opportunities.';
                break;
        }

        $stageDays = $deal->stage_updated_at?->diffInDays(now()) ?? 0;
        if ($stageDays > 7 && ! in_array($stage, ['paid', 'lost'])) {
            $actions[] = "This deal has been in the \"{$stage}\" stage for {$stageDays} days without updates. Consider following up to move it forward.";
        }

        return $actions;
    }
}
