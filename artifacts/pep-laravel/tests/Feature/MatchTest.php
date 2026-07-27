<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FoundItem;
use App\Models\LostReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    // ── Web ───────────────────────────────────────────────────────────────────

    public function test_matches_index_returns_200(): void
    {
        $response = $this->get('/matches');

        $response->assertStatus(200);
    }

    // ── API — index (all matches) ─────────────────────────────────────────────

    public function test_api_get_matches_returns_json_array(): void
    {
        $response = $this->getJson('/api/matches');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    // ── API — filter by foundItemId ───────────────────────────────────────────

    public function test_api_get_matches_filtered_by_found_item_id(): void
    {
        $found = FoundItem::factory()->storing()->create([
            'category' => '財布・カバン類',
            'features' => '黒色の財布',
        ]);

        LostReport::factory()->searching()->create([
            'category' => '財布・カバン類',
            'features' => '黒色の財布 カード',
        ]);

        LostReport::factory()->searching()->create([
            'category' => '衣類',
            'features' => '青いジャケット',
        ]);

        $response = $this->getJson("/api/matches?foundItemId={$found->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertIsArray($data);

        foreach ($data as $match) {
            $this->assertArrayHasKey('found_item',  $match);
            $this->assertArrayHasKey('lost_report', $match);
            $this->assertArrayHasKey('score',       $match);
            $this->assertArrayHasKey('reasons',     $match);
        }
    }

    // ── API — filter by lostReportId ──────────────────────────────────────────

    public function test_api_get_matches_filtered_by_lost_report_id(): void
    {
        $lost = LostReport::factory()->searching()->create([
            'category' => '電子機器',
            'features' => 'スマートフォン 黒色',
        ]);

        FoundItem::factory()->storing()->create([
            'category' => '電子機器',
            'features' => 'スマートフォン 黒色',
        ]);

        FoundItem::factory()->storing()->create([
            'category' => '傘',
            'features' => '折り畳み傘',
        ]);

        $response = $this->getJson("/api/matches?lostReportId={$lost->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertIsArray($data);

        foreach ($data as $match) {
            $this->assertArrayHasKey('found_item',  $match);
            $this->assertArrayHasKey('lost_report', $match);
            $this->assertArrayHasKey('score',       $match);
            $this->assertArrayHasKey('reasons',     $match);
        }
    }

    // ── Matching accuracy ─────────────────────────────────────────────────────

    public function test_matching_accuracy_same_category_and_keywords_yields_score_above_30(): void
    {
        $found = FoundItem::factory()->storing()->create([
            'category'       => '財布・カバン類',
            'features'       => '黒色 財布 カード 免許証',
            'found_datetime' => Carbon::parse('2024-01-15 10:00:00'),
        ]);

        LostReport::factory()->searching()->create([
            'category'           => '財布・カバン類',
            'features'           => '黒色 財布 カード 運転免許証',
            'lost_datetime_from' => Carbon::parse('2024-01-14 00:00:00'),
            'lost_datetime_to'   => Carbon::parse('2024-01-15 23:59:59'),
        ]);

        $response = $this->getJson("/api/matches?foundItemId={$found->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertNotEmpty($data, 'Expected at least one match');

        $topScore = $data[0]['score'];
        $this->assertGreaterThanOrEqual(30, $topScore, 'Top match score should be >= 30');
    }

    public function test_no_matches_returned_for_completely_different_categories(): void
    {
        $found = FoundItem::factory()->storing()->create([
            'category' => '傘',
            'features' => '折り畳み傘',
        ]);

        LostReport::factory()->searching()->count(3)->create([
            'category' => '衣類',
            'features' => 'ジャケット コート スカート',
        ]);

        $response = $this->getJson("/api/matches?foundItemId={$found->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEmpty($data, 'No matches should be returned when categories differ');
    }
}
