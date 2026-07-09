<?php

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Deal;
use App\Models\User;
use App\Services\AiDealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Company::create(['name' => 'Test Agency']);
});

test('generates stage-based action prompts for doc signed', function () {
    $deal = Deal::factory()->create([
        'name' => 'Test Deal',
        'amount' => 50000,
        'stage' => DealStage::DOC_SIGNED,
        'user_id' => $this->user->id,
        'right_to_work' => false,
        'proof_of_address' => false,
    ]);

    $service = app(AiDealService::class);
    $prompts = $service->actionPrompts($deal);

    expect($prompts)->toHaveCount(3);
    expect($prompts[0])->toContain('Right to Work');
    expect($prompts[0])->toContain('Proof of Address');
    expect($prompts[1])->toContain('Upload the signed contract');
    expect($prompts[2])->toContain('Log an introductory call');
});

test('generates fewer actions for paid stage', function () {
    $deal = Deal::factory()->create([
        'name' => 'Test Deal',
        'amount' => 50000,
        'stage' => DealStage::PAID,
        'user_id' => $this->user->id,
    ]);

    $service = app(AiDealService::class);
    $prompts = $service->actionPrompts($deal);

    expect($prompts)->toHaveCount(2);
    expect($prompts[0])->toContain('confirmation of payment');
});

test('clears cached action prompts when forget is called', function () {
    $deal = Deal::factory()->create([
        'name' => 'Test Deal',
        'amount' => 50000,
        'user_id' => $this->user->id,
        'stage' => DealStage::DOC_SENT,
        'stage_updated_at' => now()->subDays(3),
    ]);

    $cacheKey = 'deal_actions_'.$deal->id;

    $service = app(AiDealService::class);
    $service->actionPrompts($deal);

    expect(Cache::has($cacheKey))->toBeTrue();

    $service->forget($deal);

    expect(Cache::has($cacheKey))->toBeFalse();

    $result = $service->actionPrompts($deal);

    expect($result)->toBeArray();
    expect(Cache::has($cacheKey))->toBeTrue();
});
