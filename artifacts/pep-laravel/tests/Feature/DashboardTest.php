<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FoundItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // ── Web routes ────────────────────────────────────────────────────────────

    public function test_dashboard_index_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_dashboard_index_contains_stats_keys(): void
    {
        FoundItem::factory()->storing()->count(3)->create();
        FoundItem::factory()->storing()->create(['found_datetime' => now()]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('weeklyTrend');

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('storing',        $stats);
        $this->assertArrayHasKey('todayFound',      $stats);
        $this->assertArrayHasKey('nearExpiry',      $stats);
        $this->assertArrayHasKey('monthlyReturned', $stats);
        $this->assertArrayHasKey('activeReports',   $stats);
    }

    public function test_dashboard_index_contains_weekly_trend_data(): void
    {
        $response = $this->get('/');

        $weeklyTrend = $response->viewData('weeklyTrend');

        $this->assertIsArray($weeklyTrend);
        $this->assertCount(7, $weeklyTrend);

        foreach ($weeklyTrend as $day) {
            $this->assertArrayHasKey('date',     $day);
            $this->assertArrayHasKey('found',    $day);
            $this->assertArrayHasKey('returned', $day);
        }
    }

    // ── API routes ────────────────────────────────────────────────────────────

    public function test_api_dashboard_stats_returns_json_with_correct_keys(): void
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'storing',
            'todayFound',
            'nearExpiry',
            'monthlyReturned',
            'activeReports',
        ]);
    }

    public function test_api_dashboard_weekly_trend_returns_json_array_of_7_items(): void
    {
        $response = $this->getJson('/api/dashboard/weekly-trend');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertIsArray($data);
        $this->assertCount(7, $data);

        foreach ($data as $day) {
            $this->assertArrayHasKey('date',     $day);
            $this->assertArrayHasKey('found',    $day);
            $this->assertArrayHasKey('returned', $day);
        }
    }
}
