import { Router, type IRouter } from "express";
import { db, foundItemsTable, lostReportsTable } from "@workspace/db";
import { eq } from "drizzle-orm";
import { ListMatchesQueryParams, ListMatchesResponse } from "@workspace/api-zod";
import { computeMatchScore } from "../lib/matching";

const router: IRouter = Router();

router.get("/matches", async (req, res): Promise<void> => {
  const parsed = ListMatchesQueryParams.safeParse(req.query);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const { foundItemId, lostReportId, minScore } = parsed.data;
  const threshold = minScore ?? 30;

  let foundItems = await db
    .select()
    .from(foundItemsTable)
    .where(
      foundItemId
        ? eq(foundItemsTable.id, foundItemId)
        : eq(foundItemsTable.status, "保管中"),
    );

  let lostReports = await db
    .select()
    .from(lostReportsTable)
    .where(
      lostReportId
        ? eq(lostReportsTable.id, lostReportId)
        : eq(lostReportsTable.status, "探索中"),
    );

  const matches: {
    id: string;
    foundItem: (typeof foundItemsTable.$inferSelect);
    lostReport: (typeof lostReportsTable.$inferSelect);
    score: number;
    reasons: string[];
  }[] = [];

  for (const found of foundItems) {
    for (const lost of lostReports) {
      const { score, reasons } = computeMatchScore(found, lost);
      if (score >= threshold) {
        matches.push({
          id: `${found.id}-${lost.id}`,
          foundItem: found,
          lostReport: lost,
          score,
          reasons,
        });
      }
    }
  }

  matches.sort((a, b) => b.score - a.score);

  const capped = matches.slice(0, 50);

  res.json(ListMatchesResponse.parse(capped));
});

export default router;
