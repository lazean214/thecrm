<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\DealEmailLog;
use App\Models\DealHistory;
use App\Models\GdprSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GdprRetentionService
{
    public function anonymizeExpiredContacts(): int
    {
        $setting = GdprSetting::getRetentionFor('contacts');
        if (! $setting || ! $setting->is_enabled) {
            return 0;
        }

        $expiryDate = Carbon::now()->subMonths($setting->retention_months);

        $contacts = Contact::where('anonymised_at', null)
            ->where(function ($query) use ($expiryDate) {
                $query->where('last_activity_at', '<', $expiryDate)
                    ->orWhereNull('last_activity_at');
            })
            ->whereDoesntHave('deals', function ($q) {
                $q->whereNotIn('stage', ['lost', 'compliant']);
            })
            ->get();

        $count = 0;
        foreach ($contacts as $contact) {
            DB::transaction(function () use ($contact) {
                $contact->update([
                    'first_name' => 'ANON_'.substr(md5($contact->id), 0, 8),
                    'last_name' => 'ANON',
                    'email' => 'deleted_'.Str::random(16).'@gdpr.local',
                    'phone' => null,
                    'street_address' => null,
                    'city' => null,
                    'state' => null,
                    'postal_code' => null,
                    'country' => null,
                    'ni_number' => null,
                    'bank' => null,
                    'account_number' => null,
                    'sort_code' => null,
                    'date_of_birth' => null,
                    'marital_status' => null,
                    'gender' => null,
                    'anonymised_at' => now(),
                ]);
                $contact->companies()->detach();
            });
            $count++;
        }

        Log::info("GDPR: Anonymised {$count} contacts (retention: {$setting->retention_months} months)");

        return $count;
    }

    public function deleteExpiredEmailLogs(): int
    {
        $setting = GdprSetting::getRetentionFor('email_logs');
        if (! $setting || ! $setting->is_enabled) {
            return 0;
        }

        $expiryDate = Carbon::now()->subMonths($setting->retention_months);

        $count = DealEmailLog::where('created_at', '<', $expiryDate)->delete();

        Log::info("GDPR: Deleted {$count} email logs (retention: {$setting->retention_months} months)");

        return $count;
    }

    public function anonymiseExpiredActivityLogs(): int
    {
        $setting = GdprSetting::getRetentionFor('activity_logs');
        if (! $setting || ! $setting->is_enabled) {
            return 0;
        }

        $expiryDate = Carbon::now()->subMonths($setting->retention_months);

        $count = ActivityLog::whereNull('anonymised_at')
            ->where('created_at', '<', $expiryDate)
            ->update([
                'user_email' => null,
                'message' => '[anonymised]',
                'anonymised_at' => now(),
            ]);

        Log::info("GDPR: Anonymised {$count} activity logs (retention: {$setting->retention_months} months)");

        return $count;
    }

    public function anonymiseExpiredDealHistories(): int
    {
        $setting = GdprSetting::getRetentionFor('deal_histories');
        if (! $setting || ! $setting->is_enabled) {
            return 0;
        }

        $expiryDate = Carbon::now()->subMonths($setting->retention_months);

        $count = DealHistory::where('created_at', '<', $expiryDate)
            ->update([
                'old_value' => null,
                'new_value' => null,
                'details' => '[anonymised]',
                'metadata' => null,
            ]);

        Log::info("GDPR: Anonymised {$count} deal histories (retention: {$setting->retention_months} months)");

        return $count;
    }

    public function getStatistics(): array
    {
        return [
            'contacts' => [
                'total' => Contact::count(),
                'anonymised' => Contact::whereNotNull('anonymised_at')->count(),
                'pending_retention' => $this->getPendingCount(Contact::class),
            ],
            'users' => [
                'total' => User::count(),
                'anonymised' => User::whereNotNull('anonymised_at')->count(),
                'pending_retention' => $this->getPendingCount(User::class),
            ],
            'email_logs' => [
                'total' => DealEmailLog::count(),
                'retention_enabled' => GdprSetting::getRetentionFor('email_logs')?->is_enabled ?? false,
            ],
            'activity_logs' => [
                'total' => ActivityLog::count(),
                'anonymised' => Schema::hasColumn('activity_logs', 'anonymised_at')
                    ? ActivityLog::whereNotNull('anonymised_at')->count()
                    : 0,
                'retention_enabled' => GdprSetting::getRetentionFor('activity_logs')?->is_enabled ?? false,
            ],
            'deal_histories' => [
                'total' => DealHistory::count(),
                'retention_enabled' => GdprSetting::getRetentionFor('deal_histories')?->is_enabled ?? false,
            ],
        ];
    }

    protected function getPendingCount($model): int
    {
        $setting = GdprSetting::getRetentionFor((new $model)->getTable());
        if (! $setting || ! $setting->is_enabled) {
            return 0;
        }

        $expiryDate = Carbon::now()->subMonths($setting->retention_months);

        return $model::whereNull('anonymised_at')
            ->where('created_at', '<', $expiryDate)
            ->count();
    }

    public function scheduleSoftDeletionForInactiveUsers(int $months = 36): int
    {
        $cutoff = Carbon::now()->subMonths($months);

        $users = User::whereNull('anonymised_at')
            ->where('last_activity_at', '<', $cutoff)
            ->whereDoesntHave('deals', function ($query) {
                $query->whereNotIn('stage', ['closed_lost', 'compliant']);
            })
            ->get();

        foreach ($users as $user) {
            $user->update([
                'marked_for_deletion_on' => Carbon::now()->addDays(30)->toDateString(),
            ]);
        }

        return $users->count();
    }
}
