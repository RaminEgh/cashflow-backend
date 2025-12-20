<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

test('user can change password with valid current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    actingAs($user)
        ->postJson('/api/auth/change-password', [
            'current_password' => 'current-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
        ]);

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('user cannot change password with incorrect current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    actingAs($user)
        ->postJson('/api/auth/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    expect(Hash::check('current-password', $user->fresh()->password))->toBeTrue();
});

test('user cannot change password without current password', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/auth/change-password', [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

test('user cannot change password without password confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    actingAs($user)
        ->postJson('/api/auth/change-password', [
            'current_password' => 'current-password',
            'password' => 'new-password-123',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('user cannot change password with mismatched password confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    actingAs($user)
        ->postJson('/api/auth/change-password', [
            'current_password' => 'current-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('unauthenticated user cannot change password', function () {
    $this->postJson('/api/auth/change-password', [
        'current_password' => 'current-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])
        ->assertUnauthorized();
});
