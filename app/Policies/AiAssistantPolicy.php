<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class AiAssistantPolicy
{
    public function useOverdueFollowups(User $user): bool
    {
        // Admin, Sales, and Compliance can use this tool
        return $user->isAdmin() || $user->isSalesTeam() || $user->isComplianceTeam();
    }

    public function usePipelineSummary(User $user): bool
    {
        return $user->isAdmin() || $user->isSalesTeam() || $user->isComplianceTeam();
    }

    public function useStalledDeals(User $user): bool
    {
        return $user->isAdmin() || $user->isSalesTeam() || $user->isComplianceTeam();
    }

    public function useTopRevenueDeals(User $user): bool
    {
        return $user->isAdmin() || $user->isSalesTeam() || $user->isComplianceTeam();
    }

    public function useContactLookup(User $user): bool
    {
        return $user->isAdmin() || $user->isSalesTeam() || $user->isComplianceTeam();
    }

    public function useCompanyLookup(User $user): bool
    {
        return $user->isAdmin() || $user->isSalesTeam() || $user->isComplianceTeam();
    }
}
