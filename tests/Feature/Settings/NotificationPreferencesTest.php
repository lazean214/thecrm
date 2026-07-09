<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('wantsEmailNotification returns correct values', function () {
    $user = User::factory()->create([
        'notification_preferences' => [
            'deal_created' => ['email' => true],
            'deal_inactive' => ['email' => false],
        ],
    ]);

    expect($user->wantsEmailNotification('deal_created'))->toBeTrue();
    expect($user->wantsEmailNotification('deal_inactive'))->toBeFalse();
    expect($user->wantsEmailNotification('deal_commented'))->toBeFalse();
});

test('notification preferences defaults to null', function () {
    $user = User::factory()->create();

    expect($user->notification_preferences)->toBeNull();
    expect($user->wantsEmailNotification('deal_created'))->toBeFalse();
});

test('notification preferences can be saved and retrieved', function () {
    $user = User::factory()->create();

    $user->notification_preferences = [
        'deal_created' => ['email' => true],
        'deal_paid' => ['email' => true],
    ];
    $user->save();
    $user->refresh();

    expect($user->notification_preferences)->toBe([
        'deal_created' => ['email' => true],
        'deal_paid' => ['email' => true],
    ]);
});

test('notification preference can be toggled off', function () {
    $user = User::factory()->create([
        'notification_preferences' => [
            'deal_created' => ['email' => true],
        ],
    ]);

    $user->notification_preferences = [
        'deal_created' => ['email' => false],
    ];
    $user->save();
    $user->refresh();

    expect($user->wantsEmailNotification('deal_created'))->toBeFalse();
});
