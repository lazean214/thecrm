<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;

class CrmContextGatherer
{
    /**
     * Analyze the user's question and gather relevant CRM context from the database.
     *
     * @return array{data: array<string, mixed>, summary: string}
     */
    public function gather(string $question, User $user): array
    {
        $lower = strtolower($question);
        $context = [];
        $summaryParts = [];

        if ($this->matchesAny($lower, ['pipeline', 'summary', 'total', 'deals overview', 'how many deals', 'deal count'])) {
            $context['pipeline_summary'] = $this->getPipelineSummary($user);
            $summaryParts[] = 'Pipeline summary data loaded';
        }

        if ($this->matchesAny($lower, ['overdue', 'follow-up', 'follow up', 'followup', 'stuck', 'needs attention', 'late'])) {
            $context['overdue_followups'] = $this->getOverdueFollowups($user);
            $summaryParts[] = 'Overdue follow-up deals loaded';
        }

        if ($this->matchesAny($lower, ['stalled', 'no progress', 'stale', 'not moving', 'stuck deals'])) {
            $context['stalled_deals'] = $this->getStalledDeals($user);
            $summaryParts[] = 'Stalled deals loaded';
        }

        if ($this->matchesAny($lower, ['top', 'biggest', 'largest', 'highest', 'revenue', 'most valuable', 'top deals'])) {
            $context['top_revenue_deals'] = $this->getTopRevenueDeals($user);
            $summaryParts[] = 'Top revenue deals loaded';
        }

        if ($this->matchesAny($lower, ['contact', 'person', 'people', 'email', 'phone', 'who is', 'find contact', 'search contact'])) {
            $searchTerm = $this->extractSearchTerm($lower);
            $context['contacts'] = $this->getContactData($user, $searchTerm);
            $summaryParts[] = 'Contact data loaded';
        }

        if ($this->matchesAny($lower, ['company', 'companies', 'organization', 'business', 'firm', 'client company', 'corporation', 'corp'])) {
            $searchTerm = $this->extractSearchTerm($lower);
            $context['companies'] = $this->getCompanyData($user, $searchTerm);
            $summaryParts[] = 'Company data loaded';
        }

        if ($this->matchesAny($lower, ['stage', 'progress', 'where are', 'status', 'current stage'])) {
            $context['stage_distribution'] = $this->getStageDistribution($user);
            $summaryParts[] = 'Stage distribution loaded';
        }

        if (empty($context)) {
            $context['recent_deals'] = $this->getRecentDeals($user);
            $summaryParts[] = 'Recent deals loaded as fallback context';
        }

        $summary = $summaryParts ? implode('; ', $summaryParts) : 'No relevant context found';

        return [
            'data' => $context,
            'summary' => $summary,
        ];
    }

    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/', $haystack)) {
                return true;
            }
        }

        return false;
    }

    private function extractSearchTerm(string $question, ?array $keywords = null): ?string
    {
        $patterns = [
            '/(?:find|search|look\s+up|show|display|list|get)\s+(?:the\s+)?(?:contact|person|company|companies|business|corporation)\s+(?:named?|called?|is|for)\s+(.+)/i',
            '/(?:find|search|look\s+up|show|display|list|get)\s+(?:the\s+)?(?:contact|person|company|companies|business|corporation)\s+(.+)/i',
            '/(?:find|search|look\s+up|show|display|list|get)\s+(.+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $question, $matches)) {
                $term = trim($matches[1]);
                if (mb_strlen($term) >= 2) {
                    return $term;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getPipelineSummary(User $user): array
    {
        $summary = Deal::visibleTo($user)
            ->selectRaw('stage, count(*) as count, sum(amount) as total_amount')
            ->groupBy('stage')
            ->get()
            ->map(fn ($row) => [
                'stage' => $row->stage instanceof DealStage ? $row->stage->value : (string) $row->stage,
                'count' => (int) $row->count,
                'total_amount' => (float) ($row->total_amount ?? 0),
            ])
            ->toArray();

        $totalCount = array_sum(array_column($summary, 'count'));
        $totalValue = array_sum(array_column($summary, 'total_amount'));

        return [
            'stages' => $summary,
            'total_deals' => $totalCount,
            'total_value' => $totalValue,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getOverdueFollowups(User $user): array
    {
        return Deal::visibleTo($user)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('stage', DealStage::DOC_SENT->value)
                        ->where(function ($sub) {
                            $sub->where('stage_updated_at', '<', now()->subHours(24))
                                ->orWhereNull('stage_updated_at');
                        });
                })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::DOC_SIGNED->value)
                            ->where(function ($sub) {
                                $sub->where('stage_updated_at', '<', now()->subDays(2))
                                    ->orWhereNull('stage_updated_at');
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::COMPLIANT->value)
                            ->where(function ($sub) {
                                $sub->whereNotExists(function ($existsQuery) {
                                    $existsQuery->selectRaw(1)
                                        ->from('activity_logs')
                                        ->whereColumn('activity_logs.deal_id', 'deals.id')
                                        ->where('activity_logs.created_at', '>=', now()->subDays(3));
                                });
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('stage', DealStage::READY_FOR_PAYMENT->value)
                            ->where(function ($sub) {
                                $sub->where('stage_updated_at', '<', now()->subDays(7))
                                    ->orWhereNull('stage_updated_at');
                            });
                    });
            })
            ->orderBy('stage_updated_at', 'asc')
            ->limit(20)
            ->get()
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'amount' => $deal->amount,
                'stage' => $deal->stage?->value,
                'days_in_stage' => $deal->stage_updated_at ? $deal->stage_updated_at->diffInDays(now()) : 0,
                'contact' => $deal->primaryContact() ? [
                    'name' => trim($deal->primaryContact()->first_name.' '.$deal->primaryContact()->last_name),
                    'email' => $deal->primaryContact()->email,
                ] : null,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getStalledDeals(User $user, int $days = 7): array
    {
        return Deal::visibleTo($user)
            ->whereIn('stage', [
                DealStage::DOC_SENT->value,
                DealStage::DOC_SIGNED->value,
                DealStage::COMPLIANT->value,
                DealStage::READY_FOR_PAYMENT->value,
            ])
            ->where(function ($query) use ($days) {
                $query->where('stage_updated_at', '<', now()->subDays($days))
                    ->orWhereNull('stage_updated_at');
            })
            ->orderBy('stage_updated_at', 'asc')
            ->limit(20)
            ->get()
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'amount' => $deal->amount,
                'stage' => $deal->stage?->value,
                'days_in_stage' => $deal->stage_updated_at ? $deal->stage_updated_at->diffInDays(now()) : 0,
                'contact' => $deal->primaryContact() ? [
                    'name' => trim($deal->primaryContact()->first_name.' '.$deal->primaryContact()->last_name),
                    'email' => $deal->primaryContact()->email,
                ] : null,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTopRevenueDeals(User $user, int $limit = 10): array
    {
        return Deal::visibleTo($user)
            ->whereNotIn('stage', [
                DealStage::LOST->value,
            ])
            ->orderBy('amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'amount' => $deal->amount,
                'stage' => $deal->stage?->value,
                'owner' => $deal->user?->name,
                'company' => $deal->primaryCompany() ? [
                    'id' => $deal->primaryCompany()->id,
                    'name' => $deal->primaryCompany()->name,
                ] : null,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getContactData(User $user, ?string $search = null): array
    {
        $query = Contact::query()->with(['companies', 'deals']);

        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            $query->whereHas('deals', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->limit(20)
            ->get()
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => trim($contact->first_name.' '.$contact->last_name),
                'email' => $contact->email,
                'phone' => $contact->phone,
                'companies' => $contact->companies->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])->toArray(),
                'deals' => $contact->deals->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'amount' => $d->amount,
                    'stage' => $d->stage?->value,
                ])->toArray(),
            ])
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCompanyData(User $user, ?string $search = null): array
    {
        $query = Company::query()->with(['contacts', 'deals']);

        if ($user->isSalesTeam() && ! $user->isAdmin()) {
            $query->whereHas('deals', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->limit(20)
            ->get()
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'domain' => $company->domain,
                'phone' => $company->phone,
                'contacts' => $company->contacts->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => trim($c->first_name.' '.$c->last_name),
                    'email' => $c->email,
                ])->toArray(),
                'deals' => $company->deals->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'amount' => $d->amount,
                    'stage' => $d->stage?->value,
                ])->toArray(),
            ])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getStageDistribution(User $user): array
    {
        $deals = Deal::visibleTo($user)->get();

        $grouped = $deals->groupBy(fn ($deal) => $deal->stage?->value ?? 'unknown');

        return $grouped->map(function ($deals, $stage) {
            return [
                'count' => $deals->count(),
                'total_amount' => $deals->sum('amount'),
                'deal_names' => $deals->pluck('name')->take(5)->toArray(),
            ];
        })->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRecentDeals(User $user, int $limit = 5): array
    {
        return Deal::visibleTo($user)
            ->with(['contacts', 'companies'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'amount' => $deal->amount,
                'stage' => $deal->stage?->value,
                'updated_at' => $deal->updated_at->toIso8601String(),
                'contact' => $deal->primaryContact() ? [
                    'name' => trim($deal->primaryContact()->first_name.' '.$deal->primaryContact()->last_name),
                ] : null,
                'company' => $deal->primaryCompany() ? [
                    'name' => $deal->primaryCompany()->name,
                ] : null,
            ])
            ->toArray();
    }
}
