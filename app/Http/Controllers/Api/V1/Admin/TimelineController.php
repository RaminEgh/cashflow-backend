<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Common\PaginationCollection;
use App\Http\Resources\V1\Timeline\TimelineEntryCollection;
use App\Http\Resources\V1\Timeline\TimelineEntryResource;
use App\Http\Resources\V1\Timeline\TimelineGroupedResource;
use App\Models\Organ;
use App\Models\TimelineEntry;
use App\Services\Rahkaran\TimelineFetchService;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function __construct(
        private TimelineFetchService $timelineFetchService
    ) {}

    /**
     * Get timeline data for a specific organization
     */
    public function show(Request $request, Organ $organ): JsonResponse
    {
        $query = TimelineEntry::where('organ_id', $organ->id)
            ->with('organ')
            ->orderBy('date', 'desc');

        // Filter by type if provided
        if ($request->has('type') && in_array($request->type, [TimelineEntry::TYPE_INCOME, TimelineEntry::TYPE_EXPENSE])) {
            $query->where('type', $request->type);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Check if pagination is requested
        if ($request->has('per_page') || $request->has('page')) {
            $timelines = $query->paginate($request->per_page ?? 15);

            return Helper::successResponse(null, [
                'list' => new TimelineEntryCollection($timelines),
                'pagination' => new PaginationCollection($timelines),
            ]);
        }

        // Return all results without pagination
        $timelines = $query->get();

        return Helper::successResponse(null, TimelineEntryResource::collection($timelines));
    }

    /**
     * Refresh timeline data from external API
     */
    public function refresh(Organ $organ): JsonResponse
    {
        try {
            $this->timelineFetchService->fetchAndStoreForOrgan($organ);

            $timelines = TimelineEntry::where('organ_id', $organ->id)
                ->with('organ')
                ->orderBy('date', 'desc')
                ->get();

            return Helper::successResponse('Timeline data refreshed successfully', TimelineEntryResource::collection($timelines));
        } catch (\Exception $e) {
            return Helper::errorResponse('Failed to refresh timeline data: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Get timeline data grouped by date for a specific organization
     */
    public function grouped(Request $request, Organ $organ): JsonResponse
    {
        $query = TimelineEntry::where('organ_id', $organ->id)
            ->with('organ')
            ->orderBy('date', 'desc');

        // Filter by type if provided
        if ($request->has('type') && in_array($request->type, [TimelineEntry::TYPE_INCOME, TimelineEntry::TYPE_EXPENSE])) {
            $query->where('type', $request->type);
        }

        // Set default date range to current Jalali year if not provided
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (! $dateFrom && ! $dateTo) {
            $currentJalaliYear = Verta::now()->year;
            $dateFrom = Verta::parse(sprintf('%04d/%02d/%02d', $currentJalaliYear, 1, 1))->datetime()->format('Y-m-d');
            $dateTo = Verta::parse(sprintf('%04d/%02d/%02d', $currentJalaliYear, 12, 29))->datetime()->format('Y-m-d');

            // Check if it's a leap year for the last day
            if (Verta::isLeapYear($currentJalaliYear)) {
                $dateTo = Verta::parse(sprintf('%04d/%02d/%02d', $currentJalaliYear, 12, 30))->datetime()->format('Y-m-d');
            }
        }

        // Filter by date range
        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $timelineEntries = $query->get();

        // Group entries by date
        $groupedData = $this->groupTimelineEntriesByDate($timelineEntries);

        return Helper::successResponse(null, [
            'organ' => [
                'id' => $organ->id,
                'name' => $organ->name,
                'slug' => $organ->slug,
            ],
            'grouped_timeline' => TimelineGroupedResource::collection($groupedData),
            'total_entries' => $timelineEntries->count(),
            'date_range' => [
                'from' => $timelineEntries->min('date'),
                'to' => $timelineEntries->max('date'),
            ],
        ]);
    }

    /**
     * Group timeline entries by date
     */
    private function groupTimelineEntriesByDate($timelineEntries): array
    {
        $grouped = $timelineEntries->groupBy(function ($entry) {
            return $entry->date->format('Y-m-d');
        });

        $result = [];

        foreach ($grouped as $date => $entries) {
            $totalIncome = $entries->where('type', TimelineEntry::TYPE_INCOME)->sum('amount');
            $totalExpense = $entries->where('type', TimelineEntry::TYPE_EXPENSE)->sum('amount');
            $netAmount = $totalIncome - $totalExpense;

            $result[] = [
                'date' => $date,
                'date_formatted' => \Carbon\Carbon::parse($date)->format('M d, Y'),
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_amount' => $netAmount,
                'entries_count' => $entries->count(),
                'entries' => $entries->sortByDesc('created_at'),
            ];
        }

        // Sort by date descending (newest first)
        usort($result, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $result;
    }

    public function summary(Organ $organ): JsonResponse
    {
        $summary = TimelineEntry::where('organ_id', $organ->id)
            ->selectRaw('
                type,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
            ')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return Helper::successResponse(null, [
            'organ' => $organ->name,
            'organ_id' => $organ->id,
            'organ_slug' => $organ->slug,
            'summary' => $summary,
            'total_transactions' => $summary->sum('count'),
            'total_amount' => $summary->sum('total_amount'),
        ]);
    }
}
