<?php

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Deal;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard stage chart aggregates deals by stage and value', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Deal::factory()->create(['stage' => DealStage::PAID->value, 'amount' => 5000, 'user_id' => $user->id]);
    Deal::factory()->create(['stage' => DealStage::PAID->value, 'amount' => 3000, 'user_id' => $user->id]);
    Deal::factory()->create(['stage' => DealStage::DOC_SENT->value, 'amount' => 1000, 'user_id' => $user->id]);

    Livewire::test('dashboard.pipeline')
        ->assertSet('chartStage.labels', ['Paid', 'Doc Sent'])
        ->assertSet('chartStage.counts', [2, 1])
        ->assertSet('chartStage.values', [8000.0, 1000.0]);
});

test('dashboard owner chart groups pipeline value by owner', function () {
    $alice = User::factory()->create(['name' => 'Alice Owner']);
    $bob = User::factory()->create(['name' => 'Bob Owner']);
    $this->actingAs($alice);

    Deal::factory()->create(['stage' => DealStage::PAID->value, 'amount' => 4000, 'user_id' => $alice->id]);
    Deal::factory()->create(['stage' => DealStage::DOC_SENT->value, 'amount' => 6000, 'user_id' => $bob->id]);

    Livewire::test('dashboard.pipeline')
        ->assertSet('chartOwner.labels', ['Bob Owner', 'Alice Owner'])
        ->assertSet('chartOwner.values', [6000.0, 4000.0]);
});

test('dashboard company chart groups pipeline value by company', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $acme = Company::factory()->create(['name' => 'Acme Ltd']);
    $globex = Company::factory()->create(['name' => 'Globex Corp']);

    $dealA = Deal::factory()->create(['stage' => DealStage::PAID->value, 'amount' => 9000, 'user_id' => $user->id]);
    $dealA->companies()->detach();
    $dealA->companies()->attach($acme->id, ['is_primary' => true]);

    $dealB = Deal::factory()->create(['stage' => DealStage::DOC_SENT->value, 'amount' => 1000, 'user_id' => $user->id]);
    $dealB->companies()->detach();
    $dealB->companies()->attach($globex->id, ['is_primary' => true]);

    Livewire::test('dashboard.pipeline')
        ->assertSet('chartCompany.labels', ['Acme Ltd', 'Globex Corp'])
        ->assertSet('chartCompany.values', [9000.0, 1000.0]);
});

test('dashboard weekly chart shows deal totals and paid value', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Deal::factory()->create(['stage' => DealStage::PAID->value, 'amount' => 2000, 'user_id' => $user->id, 'created_at' => now()]);
    Deal::factory()->create(['stage' => DealStage::DOC_SENT->value, 'amount' => 500, 'user_id' => $user->id, 'created_at' => now()]);

    Livewire::test('dashboard.pipeline')
        ->assertSet('chartWeekly.deals', [2])
        ->assertSet('chartWeekly.paid', [2000.0]);
});

test('dashboard charts respect the owner filter', function () {
    $alice = User::factory()->create(['name' => 'Alice Owner']);
    $bob = User::factory()->create(['name' => 'Bob Owner']);
    $this->actingAs($alice);

    Deal::factory()->create(['stage' => DealStage::PAID->value, 'amount' => 4000, 'user_id' => $alice->id]);
    Deal::factory()->create(['stage' => DealStage::DOC_SENT->value, 'amount' => 6000, 'user_id' => $bob->id]);

    Livewire::test('dashboard.pipeline')
        ->set('filterUserId', $alice->id)
        ->assertSet('chartOwner.labels', ['Alice Owner'])
        ->assertSet('chartOwner.values', [4000.0]);
});
