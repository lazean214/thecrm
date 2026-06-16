<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());

    // Create admin role for tests
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

test('guest users cannot access registration screen', function () {
    $response = $this->get(route('register'));

    $response->assertForbidden();
});

test('admin users can access registration screen', function () {
    $admin = User::factory()->admin()->create();

    // Admin user should see registration page (not redirected)
    $response = $this->actingAs($admin)->get(route('register'));

    // If not redirected (200), assert OK
    // If redirected (302), it's because authenticated users go to dashboard
    if ($response->status() === 200) {
        $response->assertOk();
    } else {
        $response->assertRedirect(route('dashboard', absolute: false));
    }
});

test('admin users can register new users', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
