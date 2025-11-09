<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Organ\StoreOrganRequest;
use App\Http\Requests\Admin\Organ\UpdateOrganRequest;
use App\Http\Resources\V1\Admin\Organ\OrganCollection;
use App\Http\Resources\V1\Admin\Organ\OrganResource;
use App\Http\Resources\V1\Admin\Organ\OrganWithDepositsAndAdminsResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\Organ;
use App\Models\Role;
use App\Models\User;
use App\Services\Banking\IncomeOutgoingService as BankingIncomeOutgoingService;
use App\Services\Rahkaran\IncomeOutgoingService as RahkaranIncomeOutgoingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganController extends Controller
{
    public function __construct(
        private readonly RahkaranIncomeOutgoingService $rahkaranIncomeOutgoingService,
        private readonly BankingIncomeOutgoingService $bankingIncomeOutgoingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $organs = Organ::paginate($request->per_page ?? 10);

        return Helper::successResponse(null, [
            'list' => new OrganCollection($organs),
            'pagination' => new PaginationCollection($organs),
        ]);
    }

    public function store(StoreOrganRequest $request): JsonResponse
    {
        DB::beginTransaction();

        $organ = Organ::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($request->admins_id) {

            $adminsId = $request->admins_id;
            $organ->admins()->syncWithPivotValues($adminsId, ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);

            $role = Role::whereSlug('super-organ-admin')->first();

            $users = User::whereIn('id', $adminsId)->get();
            foreach ($users as $user) {
                $user->roles()->attach($role->id, ['assigned_by' => auth()->id()]);
            }
        }
        DB::commit();
        return Helper::successResponse(__('crud.d_created', ['source' => __('sources.organ'), 'name' => $organ->name]), new OrganResource($organ));
    }

    public function show(Organ $organ): JsonResponse
    {
        return Helper::successResponse(null, new OrganWithDepositsAndAdminsResource($organ));
    }

    public function allocation(Organ $organ, Request $request): JsonResponse
    {
        $allocation = $organ->allocations()
            ->when($request->year, fn($query, $year) => $query->where('year', $year))
            ->first();

        if (! $allocation) {
            return Helper::successResponse('No data found', [
                'list' => [],
                'pagination' => [],
            ]);
        }

        $months = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];

        $result = [];
        foreach ($months as $num => $name) {
            $rahkaranIncomeOutgoing = $this->rahkaranIncomeOutgoingService->calculateOrganMonthlyIncomeOutgoing($organ, "1404/$num");
            $bankIncomeOutgoing = $this->bankingIncomeOutgoingService->calculateOrganMonthlyIncomeOutgoing($organ, "1404/$num");
            $result[] = [
                'id' => $num + 1,
                'month' => $name,
                'budget' => $allocation["month_{$num}_budget"],
                'expense' => $allocation["month_{$num}_expense"],
                'bank_income' => $bankIncomeOutgoing['total_income'] ?? 0,
                'bank_outgoing' => $bankIncomeOutgoing['total_outgoing'] ?? 0,
                'rahkaran_income' => $rahkaranIncomeOutgoing['total_income'] ?? 0,
                'rahkaran_outgoing' => $rahkaranIncomeOutgoing['total_outgoing'] ?? 0,
            ];
        }

        return Helper::successResponse(null, [
            'year' => $allocation->year,
            'list' => $result,
        ]);
    }

    public function update(UpdateOrganRequest $request, Organ $organ): JsonResponse
    {
        DB::beginTransaction();
        $organ->update([
            ...$request->validated(),
            'updated_by' => auth()->id(),
        ]);

        if ($request->has('admins_id')) {
            $adminsId = $request->admins_id;

            if (empty($adminsId)) {
                // Detach all admins if empty array or null
                $organ->admins()->detach();
            } else {
                // Sync with provided admin IDs
                $organ->admins()->syncWithPivotValues($adminsId, ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);

                $role = Role::whereSlug('super-organ-admin')->first();

                $users = User::whereIn('id', $adminsId)->get();
                foreach ($users as $user) {
                    $user->roles()->attach($role->id, ['assigned_by' => auth()->id()]);
                }
            }
        }
        DB::commit();
        return Helper::successResponse(__('crud.d_updated', ['source' => __('sources.organ'), 'name' => $organ->name]), new OrganResource($organ));
    }

    public function delete(Organ $organ): JsonResponse
    {
        $organ->delete();

        return Helper::successResponse('موفقیت آمیز');
    }
}
