<?php

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

function createSalesUser(): User
{
    $user = User::factory()->create();
    $team = Team::firstOrCreate(['name' => 'Sales Team'], ['description' => 'Sales Team']);
    $user->teams()->attach($team);

    return $user;
}

function createComplianceUser(): User
{
    $user = User::factory()->create();
    $team = Team::firstOrCreate(['name' => 'Compliance Team'], ['description' => 'Compliance Team']);
    $user->teams()->attach($team);

    return $user;
}

// ─────────────────────────────────────────────
// Team membership
// ─────────────────────────────────────────────

test('sales user is identified as sales team', function () {
    $user = createSalesUser();

    expect($user->isSalesTeam())->toBeTrue();
    expect($user->isComplianceTeam())->toBeFalse();
});

test('compliance user is identified as compliance team', function () {
    $user = createComplianceUser();

    expect($user->isComplianceTeam())->toBeTrue();
    expect($user->isSalesTeam())->toBeFalse();
});

test('user with no team is neither sales nor compliance', function () {
    $user = User::factory()->create();

    expect($user->isSalesTeam())->toBeFalse();
    expect($user->isComplianceTeam())->toBeFalse();
});

// ─────────────────────────────────────────────
// Stage permissions
// ─────────────────────────────────────────────

test('sales user can move to doc sent, doc signed, compliant', function () {
    $user = createSalesUser();

    expect($user->canMoveToStage(DealStage::DOC_SENT->value))->toBeTrue();
    expect($user->canMoveToStage(DealStage::DOC_SIGNED->value))->toBeTrue();
    expect($user->canMoveToStage(DealStage::COMPLIANT->value))->toBeTrue();
});

test('sales user cannot move to ready for payment or paid', function () {
    $user = createSalesUser();

    expect($user->canMoveToStage(DealStage::READY_FOR_PAYMENT->value))->toBeFalse();
    expect($user->canMoveToStage(DealStage::PAID->value))->toBeFalse();
});

test('compliance user can move to all stages', function () {
    $user = createComplianceUser();

    foreach (DealStage::cases() as $stage) {
        expect($user->canMoveToStage($stage->value))->toBeTrue();
    }
});

test('user with no team can move to all stages', function () {
    $user = User::factory()->create();

    foreach (DealStage::cases() as $stage) {
        expect($user->canMoveToStage($stage->value))->toBeTrue();
    }
});

test('getAllowedDealStages returns 4 stages for sales user', function () {
    $user = createSalesUser();

    expect($user->getAllowedDealStages())->toHaveCount(4);
    expect($user->canMoveToStage(DealStage::LOST->value))->toBeTrue();
});

// ─────────────────────────────────────────────
// Stage update authorization
// ─────────────────────────────────────────────

test('sales user can update deal stage to allowed stage', function () {
    $salesUser = createSalesUser();
    $deal = Deal::factory()->create([
        'user_id' => $salesUser->id,
        'stage' => DealStage::DOC_SENT->value,
    ]);

    $this->actingAs($salesUser);

    Livewire::test('deals.table')
        ->call('updateStage', $deal->id, DealStage::DOC_SIGNED->value);

    expect($deal->fresh()->stage)->toBe(DealStage::DOC_SIGNED);
});

test('sales user cannot update deal stage to restricted stage', function () {
    $salesUser = createSalesUser();
    $deal = Deal::factory()->create([
        'user_id' => $salesUser->id,
        'stage' => DealStage::COMPLIANT->value,
    ]);

    $this->actingAs($salesUser);

    Livewire::test('deals.table')
        ->call('updateStage', $deal->id, DealStage::PAID->value);

    expect($deal->fresh()->stage)->toBe(DealStage::COMPLIANT);
});

test('sales user cannot update another users deal', function () {
    $salesUser = createSalesUser();
    $otherUser = User::factory()->create();
    $deal = Deal::factory()->create([
        'user_id' => $otherUser->id,
        'stage' => DealStage::DOC_SENT->value,
    ]);

    $this->actingAs($salesUser);

    Livewire::test('deals.table')
        ->call('updateStage', $deal->id, DealStage::DOC_SIGNED->value);

    expect($deal->fresh()->stage)->toBe(DealStage::DOC_SENT);
});

test('compliance user can update deal to any stage', function () {
    $complianceUser = createComplianceUser();
    $deal = Deal::factory()->create(['stage' => DealStage::COMPLIANT->value]);

    $this->actingAs($complianceUser);

    Livewire::test('deals.table')
        ->call('updateStage', $deal->id, DealStage::PAID->value);

    expect($deal->fresh()->stage)->toBe(DealStage::PAID);
});
