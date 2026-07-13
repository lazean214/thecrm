<?php

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'sales-team']);

    $this->salesTeam = Team::create(['name' => 'Sales Team', 'description' => 'Sales Team']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->teams()->attach($this->salesTeam);

    // Create deals in various stages
    Deal::factory()->create(['stage' => DealStage::DOC_SENT, 'amount' => 1000]);
    Deal::factory()->create(['stage' => DealStage::DOC_SIGNED, 'amount' => 2000]);
    Deal::factory()->create(['stage' => DealStage::COMPLIANT, 'amount' => 3000]);
    Deal::factory()->create(['stage' => DealStage::PAID, 'amount' => 5000]);
});

test('admin can list all kanban deals', function (): void {
    $this->actingAs($this->admin)
        ->getJson('/api/deals/kanban')
        ->assertOk()
        ->assertJsonStructure([
            'stages' => [
                'doc sent' => ['deals', 'count', 'total_amount'],
                'doc signed' => ['deals', 'count', 'total_amount'],
                'compliant' => ['deals', 'count', 'total_amount'],
                'ready for payment' => ['deals', 'count', 'total_amount'],
                'paid' => ['deals', 'count', 'total_amount'],
            ],
            'total_deals',
            'total_amount',
        ]);
});

test('admin sees all deals in kanban', function (): void {
    $this->actingAs($this->admin)
        ->getJson('/api/deals/kanban')
        ->assertOk()
        ->assertJsonPath('total_deals', 4);
});

test('sales team only sees own deals in kanban', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    Deal::factory()->create(['stage' => DealStage::DOC_SENT, 'user_id' => $salesUser->id]);

    $this->actingAs($salesUser)
        ->getJson('/api/deals/kanban')
        ->assertOk()
        ->assertJsonPath('total_deals', 1);
});

test('admin can update deal stage', function (): void {
    $deal = Deal::where('stage', DealStage::DOC_SENT)->first();

    $this->actingAs($this->admin)
        ->patchJson("/api/deals/kanban/{$deal->id}/stage", ['stage' => 'doc signed'])
        ->assertOk()
        ->assertJsonPath('deal.stage', 'doc signed')
        ->assertJsonPath('old_stage', 'doc sent')
        ->assertJsonPath('new_stage', 'doc signed');
});

test('sales team can update own deal stage', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $deal = Deal::factory()->create([
        'stage' => DealStage::DOC_SENT,
        'user_id' => $salesUser->id,
    ]);

    $this->actingAs($salesUser)
        ->patchJson("/api/deals/kanban/{$deal->id}/stage", ['stage' => 'doc signed'])
        ->assertOk()
        ->assertJsonPath('new_stage', 'doc signed');
});

test('sales team cannot update another users deal stage', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $deal = Deal::first();

    $this->actingAs($salesUser)
        ->patchJson("/api/deals/kanban/{$deal->id}/stage", ['stage' => 'doc signed'])
        ->assertForbidden();
});

test('kanban stage update validates stage value', function (): void {
    $admin = $this->admin;
    $deal = Deal::first();

    $this->actingAs($admin)
        ->patchJson("/api/deals/kanban/{$deal->id}/stage", ['stage' => 'invalid-stage'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['stage']);
});

test('kanban total amount is correct', function (): void {
    $this->actingAs($this->admin)
        ->getJson('/api/deals/kanban')
        ->assertOk()
        ->assertJsonPath('total_amount', 11000);
});

test('kanban deals can be filtered by stage', function (): void {
    $this->actingAs($this->admin)
        ->getJson('/api/deals/kanban?filterStage=paid')
        ->assertOk()
        ->assertJsonPath('total_deals', 1)
        ->assertJsonPath('stages.paid.count', 1);
});

test('kanban responds with cache header', function (): void {
    $this->actingAs($this->admin)
        ->getJson('/api/deals/kanban')
        ->assertOk()
        ->assertHeader('X-Cache');
});
