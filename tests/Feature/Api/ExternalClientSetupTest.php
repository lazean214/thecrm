<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::create(['name' => 'admin']);
});

test('external client can obtain token with valid setup key', function (): void {
    config(['app.api_setup_key' => 'test-setup-key-12345']);

    $response = $this->postJson('/api/client/setup', [
        'setup_key' => 'test-setup-key-12345',
        'client_name' => 'External CRM System',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user_id', 'message']);

    expect($response->json('user_id'))->toBeInt();
    expect($response->json('token'))->toBeString();
});

test('setup fails with invalid key', function (): void {
    config(['app.api_setup_key' => 'valid-key']);

    $this->postJson('/api/client/setup', [
        'setup_key' => 'invalid-key',
        'client_name' => 'External System',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['setup_key']);
});

test('external client can use token to access API', function (): void {
    config(['app.api_setup_key' => 'test-setup-key']);

    // Get token
    $setupResponse = $this->postJson('/api/client/setup', [
        'setup_key' => 'test-setup-key',
        'client_name' => 'Test Client',
    ]);

    $token = $setupResponse->json('token');

    // Use token to access protected endpoint
    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/users')
        ->assertOk();
});

test('setup requires both setup_key and client_name', function (): void {
    config(['app.api_setup_key' => 'test-key']);

    $this->postJson('/api/client/setup', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['setup_key', 'client_name']);
});
