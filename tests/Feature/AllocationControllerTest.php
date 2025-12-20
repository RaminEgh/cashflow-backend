<?php

use App\Constants\AdminPermissionKey;
use App\Models\Allocation;
use App\Models\Organ;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'type' => \App\Enums\UserType::Admin,
    ]);

    Sanctum::actingAs($this->user);

    $this->organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $role = Role::create([
        'slug' => 'test-admin-role',
        'label' => 'Test Admin Role',
        'user_type' => \App\Enums\UserType::Admin->value,
        'description' => 'Test role for allocation tests',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $permissions = [
        AdminPermissionKey::ALLOCATION,
        AdminPermissionKey::ALLOCATION_LIST,
        AdminPermissionKey::ALLOCATION_CREATE,
        AdminPermissionKey::ALLOCATION_SHOW,
        AdminPermissionKey::ALLOCATION_EDIT,
    ];

    foreach ($permissions as $permissionSlug) {
        Permission::create([
            'slug' => $permissionSlug,
            'label' => $permissionSlug,
            'user_type' => \App\Enums\UserType::Admin->value,
        ]);
    }

    $role->permissions()->attach(Permission::whereIn('slug', $permissions)->pluck('id')->toArray(), ['updated_by' => $this->user->id]);
    $this->user->roles()->attach($role->id, ['assigned_by' => $this->user->id]);

    $this->user->refresh();
    $this->user->load('roles');

    Cache::flush();

    Gate::before(function ($user) {
        if ($user->type === \App\Enums\UserType::Admin) {
            return true;
        }
    });
});

it('can list allocations for an organ with year', function () {
    Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Test allocation',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/admin/allocation/organ/{$this->organ->id}?year=1404");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'list',
            ],
        ]);

    expect($response->json('data.list'))->toBeArray();
    expect(count($response->json('data.list')))->toBe(12);
});

it('uses latest year when year parameter is not provided', function () {
    Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1403,
        'description' => 'Allocation 1403',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Allocation 1404',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/admin/allocation/organ/{$this->organ->id}");

    $response->assertSuccessful();
    $result = $response->json('data.list');
    expect(count($result))->toBe(12);
    expect($result[0])->toHaveKeys(['id', 'month', 'budget', 'expense', 'bank_income', 'bank_outgoing', 'rahkaran_income', 'rahkaran_outgoing']);
});

it('returns empty list when no allocations exist and year is not provided', function () {
    $response = $this->getJson("/api/admin/allocation/organ/{$this->organ->id}");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'list' => [],
            ],
        ]);
});

it('can filter allocations by year', function () {
    Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Allocation 1404',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1405,
        'description' => 'Allocation 1405',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/admin/allocation/organ/{$this->organ->id}?year=1404");

    $response->assertSuccessful();
    $result = $response->json('data.list');
    expect(count($result))->toBe(12);
    expect($result[0])->toHaveKeys(['id', 'month', 'budget', 'expense', 'bank_income', 'bank_outgoing', 'rahkaran_income', 'rahkaran_outgoing']);
});

it('can create an allocation', function () {
    $response = $this->postJson('/api/admin/allocation', [
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Test allocation description',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 500000,
        'month_2_expense' => 600000,
        'month_3_expense' => 700000,
        'month_4_expense' => 800000,
        'month_5_expense' => 900000,
        'month_6_expense' => 1000000,
        'month_7_expense' => 1100000,
        'month_8_expense' => 1200000,
        'month_9_expense' => 1300000,
        'month_10_expense' => 1400000,
        'month_11_expense' => 1500000,
        'month_12_expense' => 1600000,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'year',
                'description',
            ],
        ]);

    expect(Allocation::where('organ_id', $this->organ->id)->where('year', 1404)->exists())->toBeTrue();
});

it('cannot create duplicate allocation for same organ and year', function () {
    Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Existing allocation',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->postJson('/api/admin/allocation', [
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Duplicate allocation',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);
});

it('validates required fields when creating allocation', function () {
    $response = $this->postJson('/api/admin/allocation', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['organ_id', 'year']);
});

it('can show a single allocation', function () {
    $allocation = Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Test allocation',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/admin/allocation/{$allocation->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'year',
                'description',
            ],
        ]);

    expect($response->json('data.id'))->toBe($allocation->id);
    expect($response->json('data.year'))->toBe(1404);
});

it('can update an allocation', function () {
    $allocation = Allocation::create([
        'organ_id' => $this->organ->id,
        'year' => 1404,
        'description' => 'Original description',
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->putJson("/api/admin/allocation/{$allocation->id}", [
        'description' => 'Updated description',
        'month_1_budget' => 2000000,
        'month_2_budget' => 3000000,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

    $allocation->refresh();
    expect($allocation->description)->toBe('Updated description');
    expect($allocation->month_1_budget)->toBe(2000000);
    expect($allocation->month_2_budget)->toBe(3000000);
});

it('validates year range when creating allocation', function () {
    $response = $this->postJson('/api/admin/allocation', [
        'organ_id' => $this->organ->id,
        'year' => 1300,
        'month_1_budget' => 1000000,
        'month_2_budget' => 2000000,
        'month_3_budget' => 3000000,
        'month_4_budget' => 4000000,
        'month_5_budget' => 5000000,
        'month_6_budget' => 6000000,
        'month_7_budget' => 7000000,
        'month_8_budget' => 8000000,
        'month_9_budget' => 9000000,
        'month_10_budget' => 10000000,
        'month_11_budget' => 11000000,
        'month_12_budget' => 12000000,
        'month_1_expense' => 0,
        'month_2_expense' => 0,
        'month_3_expense' => 0,
        'month_4_expense' => 0,
        'month_5_expense' => 0,
        'month_6_expense' => 0,
        'month_7_expense' => 0,
        'month_8_expense' => 0,
        'month_9_expense' => 0,
        'month_10_expense' => 0,
        'month_11_expense' => 0,
        'month_12_expense' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['year']);
});
