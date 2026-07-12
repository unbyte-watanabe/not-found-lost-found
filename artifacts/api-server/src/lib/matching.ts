import type { FoundItem, LostReport } from "@workspace/db";

export interface MatchResult {
  score: number;
  reasons: string[];
}

/**
 * Compute a match score (0–100) between a found item and a lost report.
 */
export function computeMatchScore(
  found: FoundItem,
  lost: LostReport,
): MatchResult {
  let score = 0;
  const reasons: string[] = [];

  // Category exact match — strong signal (40 points)
  if (found.category === lost.category) {
    score += 40;
    reasons.push(`カテゴリが一致: ${found.category}`);
  } else {
    return { score: 0, reasons: ["カテゴリ不一致"] };
  }

  // Date range overlap (20 points)
  const foundDt = new Date(found.foundDatetime);
  if (lost.lostDatetimeFrom && lost.lostDatetimeTo) {
    const lostFrom = new Date(lost.lostDatetimeFrom);
    const lostTo = new Date(lost.lostDatetimeTo);
    // Found item should have been found after the item was lost
    if (foundDt >= lostFrom && foundDt <= new Date(lostTo.getTime() + 7 * 24 * 60 * 60 * 1000)) {
      score += 20;
      reasons.push("拾得日時が紛失期間内または近接");
    }
  } else if (lost.lostDatetimeFrom) {
    const lostFrom = new Date(lost.lostDatetimeFrom);
    if (foundDt >= lostFrom) {
      score += 10;
      reasons.push("拾得日時が紛失日時以降");
    }
  } else {
    // No date info — give partial credit
    score += 10;
    reasons.push("日時情報なし（部分一致）");
  }

  // Feature keyword match (up to 30 points)
  if (found.features && lost.features) {
    const foundWords = tokenize(found.features);
    const lostWords = tokenize(lost.features);
    const common = foundWords.filter((w) => lostWords.includes(w));
    if (common.length > 0) {
      const featureScore = Math.min(30, common.length * 10);
      score += featureScore;
      reasons.push(`特徴キーワード一致: ${common.slice(0, 3).join("、")}`);
    }
  }

  // Sub-category match (10 points)
  if (found.subCategory && lost.features) {
    const lostLower = lost.features.toLowerCase();
    const subCatLower = found.subCategory.toLowerCase();
    if (lostLower.includes(subCatLower) || subCatLower.includes(lostLower)) {
      score += 10;
      reasons.push(`小分類が一致: ${found.subCategory}`);
    }
  }

  return { score: Math.min(100, score), reasons };
}

function tokenize(text: string): string[] {
  // Simple tokenization: split on whitespace and common delimiters
  return text
    .toLowerCase()
    .split(/[\s、。,.\-・]+/)
    .filter((t) => t.length >= 2);
}
