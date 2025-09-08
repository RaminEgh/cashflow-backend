<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Organ\StoreOrganRequest;
use App\Http\Requests\Admin\Organ\UpdateOrganRequest;
use App\Http\Resources\V1\Admin\Organ\OrganCollection;
use App\Http\Resources\V1\Admin\Organ\OrganResource;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Models\Allocation;
use App\Models\Organ;
use App\Models\Role;
use App\Models\User;
use App\Services\Rahkaran\IncomeOutgoingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganController extends Controller
{

    public function __construct(
        private IncomeOutgoingService $incomeOutgoingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $organs = Organ::paginate($request->per_page ?? 10);
        return Helper::successResponse(null, [
            'list' => new OrganCollection($organs),
            'pagination' => new PaginationCollection($organs)
        ]);
    }

    public function store(StoreOrganRequest $request): JsonResponse
    {
        DB::beginTransaction();

        if ($request->admins_id) {
            $organ = Organ::create([
                'name' => $request->name,
                'en_name' => $request->en_name,
                'slug' => Str::slug($request->en_name),
                'phone' => $request->phone,
                'description' => $request->description,
                'logo' => $request->logo ?? '',
                'background' => $request->background ?? '',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $adminsId = $request->admins_id;
            $organ->admins()->syncWithPivotValues($adminsId, ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);

            $role = Role::whereSlug('super-organ-admin')->first();

            $users = User::whereIn('id', $adminsId)->get();
            foreach ($users as $user) {
                $user->roles()->attach($role->id, ['assigned_by' => auth()->id()]);
            }

            if ($request->year) {
                Allocation::create([
                    'organ_id' => $organ->id,
                    'year' => $request->year,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'description' => $request->allocation_description,
                    'month_1_budget' => $request->month_1_budget,
                    'month_2_budget' => $request->month_2_budget,
                    'month_3_budget' => $request->month_3_budget,
                    'month_4_budget' => $request->month_4_budget,
                    'month_5_budget' => $request->month_5_budget,
                    'month_6_budget' => $request->month_6_budget,
                    'month_7_budget' => $request->month_7_budget,
                    'month_8_budget' => $request->month_8_budget,
                    'month_9_budget' => $request->month_9_budget,
                    'month_10_budget' => $request->month_10_budget,
                    'month_11_budget' => $request->month_11_budget,
                    'month_12_budget' => $request->month_12_budget,
                    'month_1_expense' => $request->month_1_expense,
                    'month_2_expense' => $request->month_2_expense,
                    'month_3_expense' => $request->month_3_expense,
                    'month_4_expense' => $request->month_4_expense,
                    'month_5_expense' => $request->month_5_expense,
                    'month_6_expense' => $request->month_6_expense,
                    'month_7_expense' => $request->month_7_expense,
                    'month_8_expense' => $request->month_8_expense,
                    'month_9_expense' => $request->month_9_expense,
                    'month_10_expense' => $request->month_10_expense,
                    'month_11_expense' => $request->month_11_expense,
                    'month_12_expense' => $request->month_12_expense,
                ]);
            }


            return Helper::successResponse(__('crud.d_created', ['source' => __('sources.organ'), 'name' => $organ->name]), new OrganResource($organ));
        }
        DB::commit();
        return Helper::successResponse('خطا');
    }

    public function show(Organ $organ): JsonResponse
    {
        return Helper::successResponse(null, new OrganResource($organ));
    }

    public function allocation(Organ $organ, Request $request): JsonResponse
    {
        $allocation = $organ->allocations()
            ->when($request->year, fn($query, $year) => $query->where('year', $year))
            ->first();

        if (!$allocation) {
            return Helper::successResponse('No data found', [
                'list' => [],
                'pagination' => [],
            ]);
        }

        $months = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
            5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
            9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];

        $result = [];
        foreach ($months as $num => $name) {
          $incomeOutgoing = $this->incomeOutgoingService->calculateOrganMonthlyIncomeOutgoing($organ, "1404/$num");
            $result[] = [
                'id' => $num + 1,
                'month' => $name,
                'budget' => $allocation["month_{$num}_budget"],
                'expense' => $allocation["month_{$num}_expense"],
                'bank_income' => 3_000_000_000,
                'bank_outgoing' => 3_000_000_000,
                'rahkaran_income' => $num === 4 || $num === 5 ? $incomeOutgoing['total_income'] : 0,
                'rahkaran_outgoing' => $num === 4 || $num === 5 ? $incomeOutgoing['total_outgoing'] : 0,
            ];
        }


        return Helper::successResponse(null, [
            'list' => $result,
        ]);
    }

    public function update(UpdateOrganRequest $request, Organ $organ): JsonResponse
    {
        if ($request->admins_id) {
            $organ->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'description' => $request->description,
                'logo' => $request->logo ?? '',
                'background' => $request->background ?? '',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

        }
            $adminsId = $request->admins_id;
            $organ->admins()->syncWithPivotValues($adminsId, ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);

            $role = Role::whereSlug('super-organ-admin')->first();

            $users = User::whereIn('id', $adminsId)->get();
            foreach ($users as $user) {
                $user->roles()->attach($role->id, ['assigned_by' => auth()->id()]);
            }

            if ($request->year) {
                Allocation::create([
                    'organ_id' => $organ->id,
                    'year' => $request->year,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'description' => $request->allocation_description,
                    'month_1_budget' => $request->month_1_budget,
                    'month_2_budget' => $request->month_2_budget,
                    'month_3_budget' => $request->month_3_budget,
                    'month_4_budget' => $request->month_4_budget,
                    'month_5_budget' => $request->month_5_budget,
                    'month_6_budget' => $request->month_6_budget,
                    'month_7_budget' => $request->month_7_budget,
                    'month_8_budget' => $request->month_8_budget,
                    'month_9_budget' => $request->month_9_budget,
                    'month_10_budget' => $request->month_10_budget,
                    'month_11_budget' => $request->month_11_budget,
                    'month_12_budget' => $request->month_12_budget,
                    'month_1_expense' => $request->month_1_expense,
                    'month_2_expense' => $request->month_2_expense,
                    'month_3_expense' => $request->month_3_expense,
                    'month_4_expense' => $request->month_4_expense,
                    'month_5_expense' => $request->month_5_expense,
                    'month_6_expense' => $request->month_6_expense,
                    'month_7_expense' => $request->month_7_expense,
                    'month_8_expense' => $request->month_8_expense,
                    'month_9_expense' => $request->month_9_expense,
                    'month_10_expense' => $request->month_10_expense,
                    'month_11_expense' => $request->month_11_expense,
                    'month_12_expense' => $request->month_12_expense,
                ]);
            }

        return Helper::successResponse('سازمان با موفقیت ویرایش شد.');
    }

    public function delete(Organ $organ): JsonResponse
    {
        $organ->delete();
        return Helper::successResponse('موفقیت آمیز');
    }
}
