<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create admin role and assign to test user
    $role = Role::create(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole($role);
});

test('admin can list users', function (): void {
    User::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

test('non-admin cannot list users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/users')
        ->assertForbidden();
});

test('admin can create a user', function (): void {
    $payload = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/users', $payload)
        ->assertCreated()
        ->assertJsonPath('data.email', 'newuser@example.com');

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('non-admin cannot create a user', function (): void {
    $user = User::factory()->create();
    $payload = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $this->actingAs($user)
        ->postJson('/api/users', $payload)
        ->assertForbidden();
});

test('user can show own profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('admin can show any user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('non-admin cannot show another user', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/users/{$other->id}")
        ->assertForbidden();
});

test('user can update own profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson("/api/users/{$user->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

test('admin can update any user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->patchJson("/api/users/{$user->id}", ['name' => 'Updated Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

test('non-admin cannot delete another user', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/users/{$other->id}")
        ->assertForbidden();
});

test('user cannot delete own account', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/users/{$user->id}")
        ->assertForbidden();
});

test('admin can delete a user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/users/{$user->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('store user validates required fields', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/api/users', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});
