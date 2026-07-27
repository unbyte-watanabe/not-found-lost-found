<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FoundItem;
use App\Models\LostReport;
use Illuminate\Support\Collection;

/**
 * Computes similarity scores between FoundItems and LostReports.
 *
 * Scoring rubric (max 100):
 *  +40  category match (required — returns score=0 if categories differ)
 *  +20  found_datetime within [lostDatetimeFrom, lostDatetimeTo + 7 days]
 *  +10  only lostDatetimeFrom set and found_datetime >= it
 *  +10  no date information at all
 *  +10  per common keyword (tokenized), capped at +30
 *  +10  sub_category is substring of features or vice versa
 */
final class MatchingService
{
    private const DATE_BUFFER_DAYS  = 7;
    private const KEYWORD_SCORE     = 10;
    private const MAX_KEYWORD_SCORE = 30;
    private const MIN_TOKEN_LENGTH  = 2;

    /**
     * Compute the match score between a single FoundItem and a LostReport.
     *
     * @param FoundItem  $found The found item.
     * @param LostReport $lost  The lost report.
     * @return array{score: int, reasons: list<string>}
     */
    public function computeScore(FoundItem $found, LostReport $lost): array
    {
        $score   = 0;
        $reasons = [];

        // ── Category match (required gate) ───────────────────────────────────
        $foundCategory = $found->category instanceof \BackedEnum
            ? $found->category->value
            : (string) $found->category;

        if ($foundCategory !== $lost->category) {
            return ['score' => 0, 'reasons' => []];
        }

        $score     += 40;
        $reasons[]  = 'カテゴリが一致 (+40)';

        // ── Date scoring ─────────────────────────────────────────────────────
        /** @var \Carbon\Carbon|null $foundDt */
        $foundDt = $found->found_datetime;
        /** @var \Carbon\Carbon|null $fromDt */
        $fromDt  = $lost->lost_datetime_from;
        /** @var \Carbon\Carbon|null $toDt */
        $toDt    = $lost->lost_datetime_to;

        if ($fromDt !== null && $toDt !== null) {
            // Both bounds present — check with 7-day buffer on upper bound
            $upperBound = $toDt->copy()->addDays(self::DATE_BUFFER_DAYS);
            if ($foundDt >= $fromDt && $foundDt <= $upperBound) {
                $score     += 20;
                $reasons[]  = '発見日時が紛失期間内 (+20)';
            }
        } elseif ($fromDt !== null) {
            // Only lower bound — found must be on or after from
            if ($foundDt >= $fromDt) {
                $score     += 10;
                $reasons[]  = '発見日時が紛失日以降 (+10)';
            }
        } else {
            // No date information at all
            $score     += 10;
            $reasons[]  = '日時情報なし (+10)';
        }

        // ── Keyword scoring ──────────────────────────────────────────────────
        $foundTokens = $this->tokenize($found->features);
        $lostTokens  = $this->tokenize($lost->features);

        $common = array_intersect($foundTokens, $lostTokens);
        if (count($common) > 0) {
            $keywordScore = min(count($common) * self::KEYWORD_SCORE, self::MAX_KEYWORD_SCORE);
            $score       += $keywordScore;
            $reasons[]    = sprintf(
                'キーワード一致 %d件 (+%d)',
                count($common),
                $keywordScore,
            );
        }

        // ── Sub-category substring match ─────────────────────────────────────
        if ($found->sub_category !== null && $found->sub_category !== '') {
            $subCat      = mb_strtolower($found->sub_category);
            $lostFeatures = mb_strtolower($lost->features);

            if (mb_strpos($lostFeatures, $subCat) !== false || mb_strpos($subCat, $lostFeatures) !== false) {
                $score     += 10;
                $reasons[]  = 'サブカテゴリが特徴に含まれる (+10)';
            }
        }

        return ['score' => min($score, 100), 'reasons' => $reasons];
    }

    /**
     * Find all LostReports that are candidate matches for the given FoundItem.
     *
     * @param FoundItem                   $found       The found item to match against.
     * @param Collection<int, LostReport> $lostReports All lost reports to evaluate.
     * @param int                         $minScore    Minimum score threshold (default 30).
     * @return list<array{lostReport: LostReport, score: int, reasons: list<string>}>
     */
    public function findMatches(FoundItem $found, Collection $lostReports, int $minScore = 30): array
    {
        $results = [];

        foreach ($lostReports as $lost) {
            $result = $this->computeScore($found, $lost);
            if ($result['score'] >= $minScore) {
                $results[] = [
                    'lostReport' => $lost,
                    'score'      => $result['score'],
                    'reasons'    => $result['reasons'],
                ];
            }
        }

        usort($results, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Find all FoundItems that are candidate matches for the given LostReport.
     *
     * @param LostReport                  $lost       The lost report to match against.
     * @param Collection<int, FoundItem>  $foundItems All found items to evaluate.
     * @param int                         $minScore   Minimum score threshold (default 30).
     * @return list<array{foundItem: FoundItem, score: int, reasons: list<string>}>
     */
    public function findMatchesForLostReport(LostReport $lost, Collection $foundItems, int $minScore = 30): array
    {
        $results = [];

        foreach ($foundItems as $found) {
            $result = $this->computeScore($found, $lost);
            if ($result['score'] >= $minScore) {
                $results[] = [
                    'foundItem' => $found,
                    'score'     => $result['score'],
                    'reasons'   => $result['reasons'],
                ];
            }
        }

        usort($results, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Compute all pairwise matches between a collection of lost reports and found items.
     *
     * @param Collection<int, LostReport> $lostReports
     * @param Collection<int, FoundItem>  $foundItems
     * @param int                         $minScore
     * @return list<array{foundItem: FoundItem, lostReport: LostReport, score: int, reasons: list<string>}>
     */
    public function computeAllMatches(Collection $lostReports, Collection $foundItems, int $minScore = 30): array
    {
        $results = [];

        foreach ($lostReports as $lost) {
            foreach ($foundItems as $found) {
                $result = $this->computeScore($found, $lost);
                if ($result['score'] >= $minScore) {
                    $results[] = [
                        'foundItem'  => $found,
                        'lostReport' => $lost,
                        'score'      => $result['score'],
                        'reasons'    => $result['reasons'],
                    ];
                }
            }
        }

        usort($results, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Tokenize a Japanese/mixed text string into lowercase tokens.
     *
     * Splits on whitespace, Japanese punctuation, and common delimiters.
     * Keeps only tokens with mb_strlen >= MIN_TOKEN_LENGTH.
     *
     * @param string $text Input text.
     * @return list<string> Deduplicated lowercase tokens.
     */
    private function tokenize(string $text): array
    {
        $lower  = mb_strtolower($text);
        $tokens = preg_split('/[\s、。,.\\-・＊\/()【】「」]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false) {
            return [];
        }

        $filtered = array_filter(
            $tokens,
            static fn (string $t) => mb_strlen($t) >= self::MIN_TOKEN_LENGTH,
        );

        return array_values(array_unique($filtered));
    }
}
