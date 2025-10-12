<?php

use App\Models\Organ;
use App\Models\TimelineEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organ = Organ::factory()->create();
    $this->actingAs($this->user);
});

it('can fetch timeline data for an organization', function () {
    TimelineEntry::factory()->count(5)->create(['organ_id' => $this->organ->id]);

    $response = $this->getJson("/api/timeline/{$this->organ->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'type_name',
                    'title',
                    'date',
                    'amount',
                    'organ',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);

    expect($response->json('data'))->toHaveCount(5);
});

it('can filter timeline by type', function () {
    TimelineEntry::factory()->income()->count(3)->create(['organ_id' => $this->organ->id]);
    TimelineEntry::factory()->expense()->count(2)->create(['organ_id' => $this->organ->id]);

    $response = $this->getJson("/api/timeline/{$this->organ->id}?type=income");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);

    foreach ($response->json('data') as $timeline) {
        expect($timeline['type'])->toBe('income');
    }
});

it('can filter timeline by date range', function () {
    TimelineEntry::factory()->create([
        'organ_id' => $this->organ->id,
        'date' => '2024-01-15'
    ]);
    TimelineEntry::factory()->create([
        'organ_id' => $this->organ->id,
        'date' => '2024-02-15'
    ]);
    TimelineEntry::factory()->create([
        'organ_id' => $this->organ->id,
        'date' => '2024-03-15'
    ]);

    $response = $this->getJson("/api/timeline/{$this->organ->id}?date_from=2024-02-01&date_to=2024-02-28");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.date'))->toBe('2024-02-15');
});

it('returns error when organization is not found', function () {
    $response = $this->getJson('/api/timeline/999999');

    $response->assertStatus(404);
});

it('can get timeline summary statistics', function () {
    TimelineEntry::factory()->income()->count(3)->create([
        'organ_id' => $this->organ->id,
        'amount' => 1000000
    ]);
    TimelineEntry::factory()->expense()->count(2)->create([
        'organ_id' => $this->organ->id,
        'amount' => 500000
    ]);

    $response = $this->getJson("/api/timeline/{$this->organ->id}/summary");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'organ',
                'organ_id',
                'organ_slug',
                'summary',
                'total_transactions',
                'total_amount'
            ]
        ]);

    $data = $response->json('data');
    expect($data['total_transactions'])->toBe(5);
    expect($data['total_amount'])->toBe(4000000); // 3 * 1000000 + 2 * 500000
    expect($data['summary']['income']['count'])->toBe(3);
    expect($data['summary']['expense']['count'])->toBe(2);
});

it('can refresh timeline data from external api', function () {
    // Mock the external API response
    $mockData = [
        [
            'type' => 'daryaftani',
            'title' => 'فروش کالا به مشتری X',
            'date' => '2025-10-01',
            'amount' => 35000000
        ],
        [
            'type' => 'pardakhtani',
            'title' => 'خرید مواد اولیه',
            'date' => '2025-09-28',
            'amount' => 12000000
        ]
    ];

    // Mock the HTTP client
    Http::fake([
        "http://5.160.184.51:5200/timeline/{$this->organ->slug}" => Http::response($mockData)
    ]);

    $response = $this->postJson("/api/timeline/{$this->organ->id}/refresh");

    $response->assertSuccessful();

    // Verify data was stored
    expect(TimelineEntry::where('organ_id', $this->organ->id)->count())->toBe(2);

    $timeline = TimelineEntry::where('organ_id', $this->organ->id)->where('type', 'income')->first();
    expect($timeline->type)->toBe('income');
    expect($timeline->title)->toBe('فروش کالا به مشتری X');
    expect($timeline->amount)->toEqual(35000000);
});

it('can get timeline data grouped by date', function () {
    // Create timeline entries for different dates
    TimelineEntry::factory()->income()->create([
        'organ_id' => $this->organ->id,
        'date' => '2024-01-15',
        'amount' => 1000000
    ]);
    TimelineEntry::factory()->expense()->create([
        'organ_id' => $this->organ->id,
        'date' => '2024-01-15',
        'amount' => 500000
    ]);
    TimelineEntry::factory()->income()->create([
        'organ_id' => $this->organ->id,
        'date' => '2024-01-16',
        'amount' => 2000000
    ]);

    $response = $this->getJson("/api/timeline/grouped/{$this->organ->id}?date_from=2024-01-01&date_to=2024-12-31");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'organ',
                'grouped_timeline',
                'total_entries',
                'date_range'
            ]
        ]);

    $data = $response->json('data');
    expect($data['total_entries'])->toBe(3);
    expect($data['grouped_timeline'])->toHaveCount(2); // 2 different dates

    // Check first date group (2024-01-16)
    $firstGroup = $data['grouped_timeline'][0];
    expect($firstGroup['date'])->toBe('2024-01-16');
    expect($firstGroup['total_income'])->toBe(2000000);
    expect($firstGroup['total_expense'])->toBe(0);
    expect($firstGroup['net_amount'])->toBe(2000000);
    expect($firstGroup['entries_count'])->toBe(1);

    // Check second date group (2024-01-15)
    $secondGroup = $data['grouped_timeline'][1];
    expect($secondGroup['date'])->toBe('2024-01-15');
    expect($secondGroup['total_income'])->toBe(1000000);
    expect($secondGroup['total_expense'])->toBe(500000);
    expect($secondGroup['net_amount'])->toBe(500000);
    expect($secondGroup['entries_count'])->toBe(2);
});

it('handles external api errors gracefully', function () {
    Http::fake([
        "http://5.160.184.51:5200/timeline/{$this->organ->slug}" => Http::response([], 500)
    ]);

    $response = $this->postJson("/api/timeline/{$this->organ->id}/refresh");

    $response->assertStatus(500)
        ->assertJsonStructure(['message']);
});
