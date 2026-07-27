<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FoundItem;
use App\Models\LostReport;
use App\Services\MatchingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MatchingServiceTest extends TestCase
{
    private MatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchingService();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeFound(array $attrs = []): FoundItem
    {
        $item = new FoundItem();
        $item->category         = $attrs['category']         ?? '財布・カバン類';
        $item->sub_category     = $attrs['sub_category']     ?? null;
        $item->features         = $attrs['features']         ?? '';
        $item->found_datetime   = $attrs['found_datetime']   ?? null;
        $item->management_no    = $attrs['management_no']    ?? '20240101-0001';
        $item->status           = $attrs['status']           ?? '保管中';
        return $item;
    }

    private function makeLost(array $attrs = []): LostReport
    {
        $report = new LostReport();
        $report->category             = $attrs['category']             ?? '財布・カバン類';
        $report->features             = $attrs['features']             ?? '';
        $report->lost_datetime_from   = $attrs['lost_datetime_from']   ?? null;
        $report->lost_datetime_to     = $attrs['lost_datetime_to']     ?? null;
        $report->status               = $attrs['status']               ?? '探索中';
        return $report;
    }

    // ── computeScore ─────────────────────────────────────────────────────────

    public function test_computeScore_returns_0_when_categories_differ(): void
    {
        $found = $this->makeFound(['category' => '財布・カバン類']);
        $lost  = $this->makeLost(['category'  => '衣類']);

        $result = $this->service->computeScore($found, $lost);

        $this->assertSame(0, $result['score']);
        $this->assertEmpty($result['reasons']);
    }

    public function test_computeScore_returns_50_for_category_match_alone_no_date_info(): void
    {
        // No dates set on either side → +40 category +10 no-date-info = 50
        $found = $this->makeFound([
            'category'       => '財布・カバン類',
            'features'       => '',
            'found_datetime' => null,
        ]);
        $lost = $this->makeLost([
            'category'           => '財布・カバン類',
            'features'           => '',
            'lost_datetime_from' => null,
            'lost_datetime_to'   => null,
        ]);

        $result = $this->service->computeScore($found, $lost);

        // +40 category + 10 no date info = 50
        $this->assertSame(50, $result['score']);
    }

    public function test_computeScore_adds_20_when_found_datetime_is_within_lost_date_range(): void
    {
        $from = Carbon::parse('2024-01-10 00:00:00');
        $to   = Carbon::parse('2024-01-12 23:59:59');

        $found = $this->makeFound([
            'category'       => '財布・カバン類',
            'features'       => '',
            'found_datetime' => Carbon::parse('2024-01-11 12:00:00'),
        ]);
        $lost = $this->makeLost([
            'category'           => '財布・カバン類',
            'features'           => '',
            'lost_datetime_from' => $from,
            'lost_datetime_to'   => $to,
        ]);

        $result = $this->service->computeScore($found, $lost);

        // +40 category +20 date within range = 60
        $this->assertSame(60, $result['score']);
        $this->assertStringContainsString('+20', implode(' ', $result['reasons']));
    }

    public function test_computeScore_adds_10_when_only_lost_datetime_from_set_and_found_on_or_after(): void
    {
        $from = Carbon::parse('2024-01-10 00:00:00');

        $found = $this->makeFound([
            'category'       => '電子機器',
            'features'       => '',
            'found_datetime' => Carbon::parse('2024-01-15 10:00:00'),
        ]);
        $lost = $this->makeLost([
            'category'           => '電子機器',
            'features'           => '',
            'lost_datetime_from' => $from,
            'lost_datetime_to'   => null,
        ]);

        $result = $this->service->computeScore($found, $lost);

        // +40 category +10 only lower bound = 50
        $this->assertSame(50, $result['score']);
        $this->assertStringContainsString('+10', implode(' ', $result['reasons']));
    }

    public function test_computeScore_adds_keyword_points_10_per_common_token_max_30(): void
    {
        // 4 common tokens → only 30 added (capped at 30)
        $found = $this->makeFound([
            'category' => '財布・カバン類',
            'features' => '黒色 財布 カード 免許証 現金',
        ]);
        $lost = $this->makeLost([
            'category' => '財布・カバン類',
            'features' => '黒色 財布 カード 免許証 鍵',
        ]);

        $result = $this->service->computeScore($found, $lost);

        // +40 category +10 no date + at least 30 keywords (4 common, capped) = 80
        $this->assertGreaterThanOrEqual(70, $result['score']);
        $keywordReason = array_filter($result['reasons'], fn($r) => str_contains($r, 'キーワード'));
        $this->assertNotEmpty($keywordReason);
    }

    public function test_computeScore_adds_10_for_sub_category_substring_match(): void
    {
        $found = $this->makeFound([
            'category'     => '財布・カバン類',
            'sub_category' => '財布',
            'features'     => '',
        ]);
        $lost = $this->makeLost([
            'category' => '財布・カバン類',
            'features' => '財布',  // sub_category is substring of lost features
        ]);

        $result = $this->service->computeScore($found, $lost);

        // +40 category +10 no date +10 sub_category match = 60
        $this->assertGreaterThanOrEqual(60, $result['score']);
        $subReason = array_filter($result['reasons'], fn($r) => str_contains($r, 'サブカテゴリ'));
        $this->assertNotEmpty($subReason);
    }

    public function test_computeScore_caps_score_at_100(): void
    {
        // Category match + date range + 3 common keywords + sub_category match
        // = 40 + 20 + 30 + 10 = 100 exactly; add more to ensure cap
        $from = Carbon::parse('2024-01-10 00:00:00');
        $to   = Carbon::parse('2024-01-12 23:59:59');

        $found = $this->makeFound([
            'category'       => '財布・カバン類',
            'sub_category'   => '財布',
            'features'       => '黒色 財布 カード 免許証 現金 スーパー 赤',
            'found_datetime' => Carbon::parse('2024-01-11 12:00:00'),
        ]);
        $lost = $this->makeLost([
            'category'           => '財布・カバン類',
            'features'           => '黒色 財布 カード 免許証 現金 スーパー 財布',
            'lost_datetime_from' => $from,
            'lost_datetime_to'   => $to,
        ]);

        $result = $this->service->computeScore($found, $lost);

        $this->assertSame(100, $result['score']);
    }

    // ── findMatches ───────────────────────────────────────────────────────────

    public function test_findMatches_returns_only_entries_with_score_above_minScore_sorted_desc(): void
    {
        $found = $this->makeFound(['category' => '財布・カバン類', 'features' => '黒色 財布']);

        // Will score 0 (different category)
        $lostDiff = $this->makeLost(['category' => '衣類', 'features' => '黒色 財布']);

        // Will score ~50 (same category, no date, no keyword overlap)
        $lostLow = $this->makeLost(['category' => '財布・カバン類', 'features' => 'その他']);

        // Will score >= 60 (same category + keyword match)
        $lostHigh = $this->makeLost(['category' => '財布・カバン類', 'features' => '黒色 財布']);

        $collection = new Collection([$lostDiff, $lostLow, $lostHigh]);

        $results = $this->service->findMatches($found, $collection, 30);

        // $lostDiff should be excluded (score 0)
        $this->assertCount(2, $results);

        // Results must be sorted descending by score
        $this->assertGreaterThanOrEqual($results[1]['score'], $results[0]['score']);

        foreach ($results as $r) {
            $this->assertInstanceOf(LostReport::class, $r['lostReport']);
            $this->assertGreaterThanOrEqual(30, $r['score']);
        }
    }

    // ── findMatchesForLostReport ──────────────────────────────────────────────

    public function test_findMatchesForLostReport_returns_only_entries_above_minScore_sorted_desc(): void
    {
        $lost = $this->makeLost(['category' => '電子機器', 'features' => 'スマートフォン 黒色']);

        $foundDiff = $this->makeFound(['category' => '財布・カバン類', 'features' => 'スマートフォン 黒色']);
        $foundLow  = $this->makeFound(['category' => '電子機器', 'features' => 'ノート']);
        $foundHigh = $this->makeFound(['category' => '電子機器', 'features' => 'スマートフォン 黒色']);

        $collection = new Collection([$foundDiff, $foundLow, $foundHigh]);

        $results = $this->service->findMatchesForLostReport($lost, $collection, 30);

        // foundDiff scores 0 → excluded
        $this->assertCount(2, $results);

        $this->assertGreaterThanOrEqual($results[1]['score'], $results[0]['score']);

        foreach ($results as $r) {
            $this->assertInstanceOf(FoundItem::class, $r['foundItem']);
            $this->assertGreaterThanOrEqual(30, $r['score']);
        }
    }

    // ── computeAllMatches ─────────────────────────────────────────────────────

    public function test_computeAllMatches_returns_all_pairs_above_minScore(): void
    {
        $found1 = $this->makeFound(['category' => '財布・カバン類', 'features' => '黒色 財布']);
        $found2 = $this->makeFound(['category' => '傘',          'features' => '折り畳み傘']);

        $lost1 = $this->makeLost(['category' => '財布・カバン類', 'features' => '黒色 財布']);
        $lost2 = $this->makeLost(['category' => '傘',          'features' => '折り畳み傘']);
        $lost3 = $this->makeLost(['category' => '衣類',         'features' => 'ジャケット']);

        $foundCollection = new Collection([$found1, $found2]);
        $lostCollection  = new Collection([$lost1, $lost2, $lost3]);

        $results = $this->service->computeAllMatches($lostCollection, $foundCollection, 30);

        foreach ($results as $r) {
            $this->assertGreaterThanOrEqual(30, $r['score']);
            $this->assertArrayHasKey('foundItem',  $r);
            $this->assertArrayHasKey('lostReport', $r);
            $this->assertArrayHasKey('score',      $r);
            $this->assertArrayHasKey('reasons',    $r);
        }

        // Results should be sorted descending
        for ($i = 0; $i < count($results) - 1; $i++) {
            $this->assertGreaterThanOrEqual($results[$i + 1]['score'], $results[$i]['score']);
        }
    }
}
