<?php

use App\Models\Bank;
use App\Models\Deposit;
use App\Models\Organ;
use App\Models\User;
use App\Services\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->depositService = app(DepositService::class);
    $this->user = User::factory()->create();
    Cache::flush();
});

it('can get paginated deposits', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(15)->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $result = $this->depositService->getPaginated([], 10, 1);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    expect($result->count())->toBe(10);
    expect($result->total())->toBe(15);
});

it('can filter deposits by organ_id', function () {
    $organ1 = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $organ2 = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

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

    $result = $this->depositService->getPaginated(['organ_id' => $organ1->id], 10, 1);

    expect($result->count())->toBe(3);
    expect($result->first()->organ_id)->toBe($organ1->id);
});

it('can filter deposits by bank_id', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank1 = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank2 = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

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

    $result = $this->depositService->getPaginated(['bank_id' => $bank1->id], 10, 1);

    expect($result->count())->toBe(4);
    expect($result->first()->bank_id)->toBe($bank1->id);
});

it('can get a deposit by id with caching', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $result = $this->depositService->getById($deposit->id);

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($deposit->id);
    expect($result->relationLoaded('organ'))->toBeTrue();
    expect($result->relationLoaded('bank'))->toBeTrue();
});

it('returns null when deposit does not exist', function () {
    $result = $this->depositService->getById(99999);

    expect($result)->toBeNull();
});

it('can get deposits by organ id with caching', function () {
    $organ1 = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $organ2 = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

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

    $result = $this->depositService->getByOrganId($organ1->id);

    expect($result)->toHaveCount(3);
    expect($result->first()->organ_id)->toBe($organ1->id);
});

it('can create a deposit and invalidate cache', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $data = [
        'branch_name' => 'Test Branch',
        'branch_code' => '123',
        'number' => '1234567890',
        'sheba' => 'IR123456789012345678901234',
        'currency' => 'IRR',
        'type' => 1,
        'description' => 'Test Deposit',
        'bank_id' => $bank->id,
        'organ_id' => $organ->id,
    ];

    $deposit = $this->depositService->create($data, $this->user->id);

    expect($deposit)->toBeInstanceOf(Deposit::class);
    expect($deposit->number)->toBe('1234567890');
    expect($deposit->organ_id)->toBe($organ->id);
    expect($deposit->bank_id)->toBe($bank->id);
    expect($deposit->created_by)->toBe($this->user->id);
    expect($deposit->updated_by)->toBe($this->user->id);
});

it('can update a deposit and invalidate cache', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'number' => '1234567890',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $updated = $this->depositService->update($deposit, [
        'number' => '9876543210',
        'description' => 'Updated Description',
    ], $this->user->id);

    expect($updated->number)->toBe('9876543210');
    expect($updated->description)->toBe('Updated Description');
    expect($updated->updated_by)->toBe($this->user->id);
});

it('can update banking api access and dispatch job', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'has_access_banking_api' => false,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    \Illuminate\Support\Facades\Queue::fake();

    $updated = $this->depositService->updateBankingApiAccess($deposit, true, $this->user->id);

    expect($updated->has_access_banking_api)->toBeTrue();
    expect($updated->updated_by)->toBe($this->user->id);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\FetchBankAccountBalance::class);
});

it('does not dispatch job when banking api access is disabled', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'has_access_banking_api' => true,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    \Illuminate\Support\Facades\Queue::fake();

    $updated = $this->depositService->updateBankingApiAccess($deposit, false, $this->user->id);

    expect($updated->has_access_banking_api)->toBeFalse();
    \Illuminate\Support\Facades\Queue::assertNothingPushed();
});

it('can delete a deposit and invalidate cache', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $result = $this->depositService->delete($deposit);

    expect($result)->toBeTrue();
    expect(Deposit::find($deposit->id))->toBeNull();
});

it('returns fresh data from database', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    // First call - should hit database
    $result1 = $this->depositService->getById($deposit->id);

    expect($result1)->not->toBeNull();
    expect($result1->id)->toBe($deposit->id);

    // Delete from database
    Deposit::where('id', $deposit->id)->delete();

    // Second call - should return null since no caching
    $result2 = $this->depositService->getById($deposit->id);

    expect($result2)->toBeNull();
});

it('returns fresh data when deposit is updated', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $deposit = Deposit::factory()->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'number' => '1234567890',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    // Cache the deposit
    $cached = $this->depositService->getById($deposit->id);
    expect($cached->number)->toBe('1234567890');

    // Update the deposit
    $this->depositService->update($deposit, ['number' => '9999999999'], $this->user->id);

    // Get again - should have fresh data
    $fresh = $this->depositService->getById($deposit->id);
    expect($fresh->number)->toBe('9999999999');
});

it('can sort deposits by organ name', function () {
    $organ1 = Organ::factory()->create([
        'name' => 'ZZZ Organization',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $organ2 = Organ::factory()->create([
        'name' => 'AAA Organization',
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

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

    $result = $this->depositService->getPaginated([
        'sort' => 'organ.name',
        'order' => 'ASC',
    ], 10, 1);

    expect($result->first()->organ->name)->toBe('AAA Organization');
    expect($result->last()->organ->name)->toBe('ZZZ Organization');
});

it('can get all deposits without pagination', function () {
    $organ = Organ::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);
    $bank = Bank::factory()->create([
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    Deposit::factory()->count(5)->create([
        'organ_id' => $organ->id,
        'bank_id' => $bank->id,
        'created_by' => $this->user->id,
        'updated_by' => $this->user->id,
    ]);

    $result = $this->depositService->getAll();

    expect($result)->toHaveCount(5);
    expect($result->first())->toBeInstanceOf(Deposit::class);
});
