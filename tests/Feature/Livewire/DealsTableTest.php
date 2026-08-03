<?php

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('deals table component renders', function () {
    $user = User::factory()->create();

    Deal::create([
        'user_id' => $user->id,
        'name' => 'Alpha Deal',
        'amount' => 1000,
        'stage' => DealStage::DOC_SENT->value,
    ]);

    $this->actingAs($user);

    Livewire::test('deals.table')
        ->assertStatus(200)
        ->assertSee('Alpha Deal');
});

test('deals can be filtered by name', function () {
    $user = User::factory()->create();

    Deal::create([
        'user_id' => $user->id,
        'name' => 'Alpha Deal',
        'amount' => 1000,
        'stage' => DealStage::DOC_SENT->value,
    ]);

    Deal::create([
        'user_id' => $user->id,
        'name' => 'Beta Deal',
        'amount' => 2000,
        'stage' => DealStage::DOC_SENT->value,
    ]);

    $this->actingAs($user);

    Livewire::test('deals.table')
        ->set('filterDealName', 'Alpha')
        ->assertSee('Alpha Deal')
        ->assertDontSee('Beta Deal');
});

test('view mode can be toggled', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('deals.table')
        ->assertSet('view', 'kanban')
        ->call('setView', 'table')
        ->assertSet('view', 'table')
        ->call('setView', 'kanban')
        ->assertSet('view', 'kanban');
});

test('deal stage can be updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $deal = Deal::create([
        'user_id' => $user->id,
        'name' => 'Gamma Deal',
        'amount' => 3000,
        'stage' => DealStage::DOC_SENT->value,
    ]);

    Livewire::test('deals.table')
        ->call('updateStage', $deal->id, DealStage::DOC_SIGNED->value);

    expect($deal->fresh()->stage)->toBe(DealStage::DOC_SIGNED);
});

test('load more in stage returns expanded stage data without losing other stages', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    foreach (range(1, 61) as $i) {
        Deal::create([
            'user_id' => $user->id,
            'name' => "Doc Sent Deal $i",
            'amount' => 100,
            'stage' => DealStage::DOC_SENT->value,
        ]);
    }

    Deal::create([
        'user_id' => $user->id,
        'name' => 'Signed Deal',
        'amount' => 200,
        'stage' => DealStage::DOC_SIGNED->value,
    ]);

    Livewire::test('deals.table')
        ->call('showAllTime')
        ->call('loadMoreInStage', DealStage::DOC_SENT->value)
        ->assertReturned(function ($result) {
            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['deals', 'count', 'total_amount', 'has_more', 'offset']);
            expect($result['count'])->toBe(61);
            expect($result['offset'])->toBe(100);
            expect($result['has_more'])->toBeFalse();
            expect($result['deals'])->toHaveCount(61);

            return true;
        })
        ->assertNotSet('kanbanData.doc sent', null);
});
