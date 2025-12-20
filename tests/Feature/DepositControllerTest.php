<?php

use App\Constants\AdminPermissionKey;
use App\Models\Bank;
use App\Models\Deposit;
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

    $role = Role::create([
        'slug' => 'test-deposit-admin-role',
        'label' => 'Test Deposit Admin Role',
        'user_type' => \App\Enums\UserType::Admin->value,
        'description' => 'Test role for deposit tests',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $permissions = [
        AdminPermissionKey::DEPOSIT,
        AdminPermissionKey::ORGAN_LIST,
        AdminPermissionKey::ORGAN_SHOW,
        AdminPermissionKey::ORGAN_CREATE,
        AdminPermissionKey::ORGAN_EDIT,
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

it('can fetch all deposits', function () {
    $organ = Organ::factory()->create();
    $bank = Bank::factory()->create();

    Deposit::factory()->count(5)->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/admin/deposit');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'list' => [
                    '*' => [
                        'id',
                        'organ',
                        'bank',
                        'number',
                    ],
                ],
                'pagination',
            ],
        ]);

    expect($response->json('data.list'))->toHaveCount(5);
});

it('can toggle deposit banking api access', function () {
    $organ = Organ::factory()->create();
    $bank = Bank::factory()->create();

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'has_access_banking_api' => false,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->patchJson("/api/admin/deposit/{$deposit->id}/banking-api-access", [
        'has_access_banking_api' => true,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.has_access_banking_api', true);

    $deposit->refresh();
    expect($deposit->has_access_banking_api)->toBeTrue();
});

it('prevents non-admin from toggling deposit banking api access', function () {
    $organ = Organ::factory()->create();
    $bank = Bank::factory()->create();

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'has_access_banking_api' => false,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $organUser = User::factory()->create([
        'type' => \App\Enums\UserType::Organ,
    ]);

    Sanctum::actingAs($organUser);

    $response = $this->patchJson("/api/admin/deposit/{$deposit->id}/banking-api-access", [
        'has_access_banking_api' => true,
    ]);

    $response->assertForbidden();
});

it('can filter deposits by organ_id', function () {
    $organ1 = Organ::factory()->create();
    $organ2 = Organ::factory()->create();
    $bank = Bank::factory()->create();

    Deposit::factory()->count(3)->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(2)->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $filter = json_encode(['organ_id' => $organ1->id]);
    $response = $this->getJson("/api/admin/deposit?filter={$filter}");

    $response->assertSuccessful();
    expect($response->json('data.list'))->toHaveCount(3);

    foreach ($response->json('data.list') as $deposit) {
        expect($deposit['organ']['id'])->toBe($organ1->id);
    }
});

it('can filter deposits by bank_id', function () {
    $organ = Organ::factory()->create();
    $bank1 = Bank::factory()->create();
    $bank2 = Bank::factory()->create();

    Deposit::factory()->count(4)->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank1->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(2)->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank2->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $filter = json_encode(['bank_id' => $bank1->id]);
    $response = $this->getJson("/api/admin/deposit?filter={$filter}");

    $response->assertSuccessful();
    expect($response->json('data.list'))->toHaveCount(4);

    foreach ($response->json('data.list') as $deposit) {
        expect($deposit['bank']['id'])->toBe($bank1->id);
    }
});

it('can filter deposits by both organ_id and bank_id', function () {
    $organ1 = Organ::factory()->create();
    $organ2 = Organ::factory()->create();
    $bank1 = Bank::factory()->create();
    $bank2 = Bank::factory()->create();

    Deposit::factory()->count(3)->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank1->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(2)->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank2->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(2)->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank1->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $filter = json_encode(['organ_id' => $organ1->id, 'bank_id' => $bank1->id]);
    $response = $this->getJson("/api/admin/deposit?filter={$filter}");

    $response->assertSuccessful();
    expect($response->json('data.list'))->toHaveCount(3);

    foreach ($response->json('data.list') as $deposit) {
        expect($deposit['organ']['id'])->toBe($organ1->id);
        expect($deposit['bank']['id'])->toBe($bank1->id);
    }
});

it('can sort deposits by organ.name', function () {
    $organ1 = Organ::factory()->create(['name' => 'AAA Organization']);
    $organ2 = Organ::factory()->create(['name' => 'ZZZ Organization']);
    $bank = Bank::factory()->create();

    Deposit::factory()->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/admin/deposit?sort=organ.name&order=ASC');

    $response->assertSuccessful();
    $deposits = $response->json('data.list');
    expect($deposits[0]['organ']['name'])->toBe('AAA Organization');
    expect($deposits[1]['organ']['name'])->toBe('ZZZ Organization');
});

it('can sort deposits by organ.name in descending order', function () {
    $organ1 = Organ::factory()->create(['name' => 'AAA Organization']);
    $organ2 = Organ::factory()->create(['name' => 'ZZZ Organization']);
    $bank = Bank::factory()->create();

    Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/admin/deposit?sort=organ.name&order=DESC');

    $response->assertSuccessful();
    $deposits = $response->json('data.list');
    expect($deposits[0]['organ']['name'])->toBe('ZZZ Organization');
    expect($deposits[1]['organ']['name'])->toBe('AAA Organization');
});

it('can paginate deposits', function () {
    $organ = Organ::factory()->create();
    $bank = Bank::factory()->create();

    Deposit::factory()->count(15)->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/admin/deposit?perPage=10&page=1');

    $response->assertSuccessful();
    expect($response->json('data.list'))->toHaveCount(10);
    expect($response->json('data.pagination.total'))->toBe(15);
    expect($response->json('data.pagination.per_page'))->toBe(10);
});

it('can combine filtering and sorting', function () {
    $organ1 = Organ::factory()->create(['name' => 'BBB Organization']);
    $organ2 = Organ::factory()->create(['name' => 'AAA Organization']);
    $bank = Bank::factory()->create();

    Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $filter = json_encode(['bank_id' => $bank->id]);
    $response = $this->getJson("/api/admin/deposit?filter={$filter}&sort=organ.name&order=ASC");

    $response->assertSuccessful();
    $deposits = $response->json('data.list');
    expect($deposits)->toHaveCount(2);
    expect($deposits[0]['organ']['name'])->toBe('AAA Organization');
    expect($deposits[1]['organ']['name'])->toBe('BBB Organization');
});

it('can filter deposits using direct query parameters', function () {
    $organ1 = Organ::factory()->create();
    $organ2 = Organ::factory()->create();
    $bank1 = Bank::factory()->create();
    $bank2 = Bank::factory()->create();

    Deposit::factory()->count(3)->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank1->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(2)->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank1->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(2)->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank2->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/admin/deposit?organ_id={$organ1->id}&bank_id={$bank1->id}");

    $response->assertSuccessful();
    expect($response->json('data.list'))->toHaveCount(3);

    foreach ($response->json('data.list') as $deposit) {
        expect($deposit['organ']['id'])->toBe($organ1->id);
        expect($deposit['bank']['id'])->toBe($bank1->id);
    }
});

it('can sort deposits using sort_by and sort_order parameters', function () {
    $organ1 = Organ::factory()->create(['name' => 'AAA Organization']);
    $organ2 = Organ::factory()->create(['name' => 'ZZZ Organization']);
    $bank = Bank::factory()->create();

    Deposit::factory()->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/admin/deposit?sort_by=organ.name&sort_order=ASC');

    $response->assertSuccessful();
    $deposits = $response->json('data.list');
    expect($deposits[0]['organ']['name'])->toBe('AAA Organization');
    expect($deposits[1]['organ']['name'])->toBe('ZZZ Organization');
});

it('can combine direct query parameters with sort_by and sort_order', function () {
    $organ1 = Organ::factory()->create(['name' => 'BBB Organization']);
    $organ2 = Organ::factory()->create(['name' => 'AAA Organization']);
    $bank = Bank::factory()->create();

    Deposit::factory()->create([
        'organ_id' => $organ1->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->create([
        'organ_id' => $organ2->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/admin/deposit?bank_id={$bank->id}&sort_by=organ.name&sort_order=ASC&per_page=10&page=1");

    $response->assertSuccessful();
    $deposits = $response->json('data.list');
    expect($deposits)->toHaveCount(2);
    expect($deposits[0]['organ']['name'])->toBe('AAA Organization');
    expect($deposits[1]['organ']['name'])->toBe('BBB Organization');
});
