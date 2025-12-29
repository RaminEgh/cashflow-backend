<?php

namespace App\Services;

use App\Jobs\FetchBankAccountBalance;
use App\Models\Deposit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DepositService
{
    /**
     * Get paginated deposits with filtering and sorting
     */
    public function getPaginated(array $filters = [], int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = Deposit::with(['organ', 'bank']);

        if (isset($filters['organ_id'])) {
            $query->where('organ_id', $filters['organ_id']);
        }

        if (isset($filters['bank_id'])) {
            $query->where('bank_id', $filters['bank_id']);
        }

        // Handle sorting
        $sortField = $filters['sort'] ?? $filters['sort_by'] ?? null;
        $sortOrder = $filters['order'] ?? $filters['sort_order'] ?? 'ASC';

        if ($sortField) {
            // Handle relationship-based sorting (e.g., organ.name)
            if (str_contains($sortField, '.')) {
                [$relation, $field] = explode('.', $sortField, 2);

                if ($relation === 'organ') {
                    $query->join('organs', 'deposits.organ_id', '=', 'organs.id')
                        ->select('deposits.*')
                        ->orderBy("organs.{$field}", $sortOrder);
                } elseif ($relation === 'bank') {
                    $query->join('banks', 'deposits.bank_id', '=', 'banks.id')
                        ->select('deposits.*')
                        ->orderBy("banks.{$field}", $sortOrder);
                }
            } else {
                $query->orderBy($sortField, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get all deposits
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Deposit::with(['organ', 'bank']);

        if (isset($filters['organ_id'])) {
            $query->where('organ_id', $filters['organ_id']);
        }

        if (isset($filters['bank_id'])) {
            $query->where('bank_id', $filters['bank_id']);
        }

        return $query->latest()->get();
    }

    /**
     * Get a single deposit by ID
     */
    public function getById(int $id): ?Deposit
    {
        return Deposit::with(['organ', 'bank', 'balances'])->find($id);
    }

    /**
     * Get deposits by organ ID
     */
    public function getByOrganId(int $organId): Collection
    {
        return Deposit::where('organ_id', $organId)->with(['organ', 'bank'])->get();
    }

    /**
     * Create a new deposit
     */
    public function create(array $data, int $userId): Deposit
    {
        $deposit = DB::transaction(function () use ($data, $userId) {
            return Deposit::create([
                ...$data,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });

        return $deposit->load(['organ', 'bank', 'balances']);
    }

    /**
     * Update a deposit
     */
    public function update(Deposit $deposit, array $data, int $userId): Deposit
    {
        DB::transaction(function () use ($deposit, $data, $userId) {
            $deposit->update([
                ...$data,
                'updated_by' => $userId,
            ]);
        });

        return $deposit->fresh(['organ', 'bank', 'balances']);
    }

    /**
     * Update banking API access for a deposit
     */
    public function updateBankingApiAccess(Deposit $deposit, bool $hasAccess, int $userId): Deposit
    {
        DB::transaction(function () use ($deposit, $hasAccess, $userId) {
            $deposit->has_access_banking_api = $hasAccess;
            $deposit->updated_by = $userId;
            $deposit->save();

            if ($hasAccess) {
                FetchBankAccountBalance::dispatch($deposit);
            }
        });

        return $deposit->fresh(['organ', 'bank', 'balances']);
    }

    /**
     * Delete a deposit
     */
    public function delete(Deposit $deposit): bool
    {
        return $deposit->delete();
    }
}
