<?php

namespace App\Services\Rahkaran;

use App\Models\Organ;
use App\Models\TimelineEntry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TimelineFetchService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.timeline.base_url', 'http://5.160.184.51:5200');
    }

    /**
     * Fetch and store timeline data for all organizations
     */
    public function fetchAndStore(): void
    {
        $organs = Organ::all();

        foreach ($organs as $organ) {
            try {
                $this->fetchAndStoreForOrgan($organ);
                Log::info("Timeline data fetched successfully for organ: {$organ->slug}");
            } catch (\Exception $e) {
                Log::error("Failed to fetch timeline for organ {$organ->slug}: " . $e->getMessage());
            }
        }
    }

    /**
     * Fetch and store timeline data for a specific organization
     */
    public function fetchAndStoreForOrgan(Organ $organ): void
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/timeline/{$organ->slug}");

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch timeline data for organ: {$organ->slug}. Status: {$response->status()}");
        }

        $timelineData = $response->json();

        if (!is_array($timelineData)) {
            Log::warning("Invalid timeline data format for organ: {$organ->slug}");
            return;
        }

        // Clear existing timeline entries for this organ
        TimelineEntry::where('organ_id', $organ->id)->delete();

        // Store new timeline entries
        foreach ($timelineData as $item) {
            $this->storeTimelineEntry($organ, $item);
        }

        Log::info("Stored " . count($timelineData) . " timeline entries for organ: {$organ->slug}");
    }

    /**
     * Store a single timeline entry
     */
    private function storeTimelineEntry(Organ $organ, array $item): void
    {
        // Validate required fields
        if (!isset($item['type'], $item['title'], $item['date'], $item['amount'])) {
            Log::warning("Invalid timeline entry format", ['item' => $item]);
            return;
        }

        // Map Persian types to English
        $typeMapping = [
            'daryaftani' => TimelineEntry::TYPE_INCOME,
            'pardakhtani' => TimelineEntry::TYPE_EXPENSE,
        ];

        $englishType = $typeMapping[$item['type']] ?? null;
        if (!$englishType) {
            Log::warning("Invalid timeline entry type: {$item['type']}");
            return;
        }

        TimelineEntry::create([
            'organ_id' => $organ->id,
            'type' => $englishType,
            'title' => $item['title'],
            'date' => $item['date'],
            'amount' => $item['amount'],
        ]);
    }

    /**
     * Get timeline data for a specific organization from external API
     */
    public function getTimelineForOrgan(Organ $organ): array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/timeline/{$organ->slug}");

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch timeline data for organ: {$organ->slug}. Status: {$response->status()}");
        }

        return $response->json();
    }
}
