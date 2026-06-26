<?php

use App\Models\Deal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'sales-team']);

    // Create teams
    $this->salesTeam = Team::create(['name' => 'Sales Team', 'description' => 'Sales Team']);

    // Create admin user
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->teams()->attach($this->salesTeam);
});

test('admin can list all deals', function (): void {
    Deal::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/deals')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('sales team can only see their own deals', function (): void {
    // Create another user first (so factory doesn't assign deals to sales user)
    $otherUser = User::factory()->create();
    Deal::factory()->create(['user_id' => $otherUser->id]);

    // Create sales user
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    // Create deals for the sales user
    Deal::factory()->create(['user_id' => $salesUser->id]);
    Deal::factory()->create(['user_id' => $salesUser->id]);

    $this->actingAs($salesUser)
        ->getJson('/api/deals')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can create a deal', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/deals', ['name' => 'Acme Deal'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Deal');

    $this->assertDatabaseHas('deals', ['name' => 'Acme Deal']);
});

test('deal is automatically assigned to authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/deals', ['name' => 'Acme Deal'])
        ->assertCreated();

    // The user_id should be set to the authenticated user
    $this->assertDatabaseHas('deals', [
        'name' => 'Acme Deal',
        'user_id' => $user->id,
    ]);
});

test('sales team can view own deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $deal = Deal::factory()->create(['user_id' => $salesUser->id]);

    $this->actingAs($salesUser)
        ->getJson("/api/deals/{$deal->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $deal->id);
});

test('sales team cannot view another users deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $otherUser = User::factory()->create();
    $deal = Deal::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($salesUser)
        ->getJson("/api/deals/{$deal->id}")
        ->assertForbidden();
});

test('admin can view any deal', function (): void {
    $deal = Deal::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/deals/{$deal->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $deal->id);
});

test('sales team can update own deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $deal = Deal::factory()->create(['user_id' => $salesUser->id]);

    $this->actingAs($salesUser)
        ->patchJson("/api/deals/{$deal->id}", ['name' => 'Updated Deal'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Deal');
});

test('sales team cannot update another users deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $otherUser = User::factory()->create();
    $deal = Deal::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($salesUser)
        ->patchJson("/api/deals/{$deal->id}", ['name' => 'Updated Deal'])
        ->assertForbidden();
});

test('admin can delete a deal', function (): void {
    $deal = Deal::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/deals/{$deal->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
});

test('non-admin cannot delete a deal', function (): void {
    $user = User::factory()->create();
    $deal = Deal::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/deals/{$deal->id}")
        ->assertForbidden();
});

test('store deal validates required fields', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/deals', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
