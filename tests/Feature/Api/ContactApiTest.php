<?php

use App\Models\Contact;
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

test('admin can list all contacts', function (): void {
    Contact::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/contacts')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('sales team can only see contacts linked to their deals', function (): void {
    // Create sales user
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    // Create other user's deal first (so factory doesn't assign it to salesUser)
    $otherUser = User::factory()->create();
    Deal::factory()->create(['user_id' => $otherUser->id]);

    // Now create deal for sales user - factory auto-creates 1 contact
    $deal1 = Deal::factory()->create(['user_id' => $salesUser->id]);

    // Sales user should see contacts from deal1 (their deal)
    // NOT from other user's deal
    $this->actingAs($salesUser)
        ->getJson('/api/contacts')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('can create a contact', function (): void {
    $payload = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/contacts', $payload)
        ->assertCreated()
        ->assertJsonPath('data.email', 'jane@example.com');

    $this->assertDatabaseHas('contacts', ['email' => 'jane@example.com']);
});

test('admin can view any contact', function (): void {
    $contact = Contact::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/contacts/{$contact->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $contact->id);
});

test('sales team can view contact linked to their deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $deal = Deal::factory()->create(['user_id' => $salesUser->id]);
    $contact = Contact::factory()->create();
    $deal->contacts()->attach($contact->id, ['is_primary' => true]);

    $this->actingAs($salesUser)
        ->getJson("/api/contacts/{$contact->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $contact->id);
});

test('sales team cannot view contact not linked to their deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $otherUser = User::factory()->create();
    $deal = Deal::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create();
    $deal->contacts()->attach($contact->id, ['is_primary' => true]);

    $this->actingAs($salesUser)
        ->getJson("/api/contacts/{$contact->id}")
        ->assertForbidden();
});

test('admin can update any contact', function (): void {
    $contact = Contact::factory()->create();

    $this->actingAs($this->admin)
        ->patchJson("/api/contacts/{$contact->id}", ['first_name' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Updated');
});

test('sales team can update contact linked to their deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $deal = Deal::factory()->create(['user_id' => $salesUser->id]);
    $contact = Contact::factory()->create();
    $deal->contacts()->attach($contact->id, ['is_primary' => true]);

    $this->actingAs($salesUser)
        ->patchJson("/api/contacts/{$contact->id}", ['first_name' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Updated');
});

test('sales team cannot update contact not linked to their deal', function (): void {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales-team');
    $salesUser->teams()->attach($this->salesTeam);

    $otherUser = User::factory()->create();
    $deal = Deal::factory()->create(['user_id' => $otherUser->id]);
    $contact = Contact::factory()->create();
    $deal->contacts()->attach($contact->id, ['is_primary' => true]);

    $this->actingAs($salesUser)
        ->patchJson("/api/contacts/{$contact->id}", ['first_name' => 'Updated'])
        ->assertForbidden();
});

test('admin can delete a contact', function (): void {
    $contact = Contact::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/contacts/{$contact->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
});

test('non-admin cannot delete a contact', function (): void {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/contacts/{$contact->id}")
        ->assertForbidden();
});

test('store contact validates required fields', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/api/contacts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name']);
});
