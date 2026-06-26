<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\DealStage;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user is an administrator
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function dealEmailLogs()
    {
        return $this->hasMany(
            DealEmailLog::class
        );
    }

    public function deals()
    {
        return $this->hasMany(
            Deal::class
        );
    }

    public function teams()
    {
        return $this->belongsToMany(
            Team::class,
            'team_user'
        );
    }

    /**
     * Check if user belongs to a specific team by name
     */
    public function belongsToTeam($teamName): bool
    {
        return $this->teams()
            ->where('name', $teamName)
            ->exists();
    }

    /**
     * Check if user is in Sales Team
     */
    public function isSalesTeam(): bool
    {
        return $this->belongsToTeam('Sales Team');
    }

    /**
     * Check if user is in Compliance Team
     */
    public function isComplianceTeam(): bool
    {
        return $this->belongsToTeam('Compliance Team');
    }

    /**
     * Get allowed deal stages for this user
     *
     * Compliance Team: Can access all stages
     * Sales Team: Can only move deals to Doc Sent, Doc Signed, Compliant
     * Admin: All stages available
     */
    public function getAllowedDealStages(): array
    {
        if ($this->isComplianceTeam()) {
            // Compliance can move to all stages
            return array_map(
                fn ($case) => $case->value,
                DealStage::cases()
            );
        }

        if ($this->isSalesTeam()) {
            // Sales can only move to these stages
            return [
                DealStage::DOC_SENT->value,
                DealStage::DOC_SIGNED->value,
                DealStage::COMPLIANT->value,
                DealStage::LOST->value,
            ];
        }

        // Default: no restrictions if not in a team
        return array_map(
            fn ($case) => $case->value,
            DealStage::cases()
        );
    }

    /**
     * Check if user can move a deal to a specific stage
     */
    public function canMoveToStage($stage): bool
    {
        return in_array($stage, $this->getAllowedDealStages());
    }

    /**
     * Get allowed stage values for authorized movement
     * Used in frontend to show/disable stage buttons
     */
    public function getEditableStages(): array
    {
        return $this->getAllowedDealStages();
    }
}
