import { Router, type IRouter } from "express";
import { db, foundItemsTable, lostReportsTable } from "@workspace/db";
import { eq, and, gte, lte, count, sql } from "drizzle-orm";
import {
  GetDashboardStatsResponse,
  GetDashboardWeeklyTrendResponse,
} from "@workspace/api-zod";

const router: IRouter = Router();

router.get("/dashboard/stats", async (req, res): Promise<void> => {
  const now = new Date();
  const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const twoMonthsAgo = new Date(now);
  twoMonthsAgo.setMonth(twoMonthsAgo.getMonth() - 2);
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

  const [storingCount] = await db
    .select({ count: count() })
    .from(foundItemsTable)
    .where(eq(foundItemsTable.status, "保管中"));

  const [todayFoundCount] = await db
    .select({ count: count() })
    .from(foundItemsTable)
    .where(gte(foundItemsTable.foundDatetime, todayStart));

  const [nearExpiryCount] = await db
    .select({ count: count() })
    .from(foundItemsTable)
    .where(
      and(
        eq(foundItemsTable.status, "保管中"),
        lte(foundItemsTable.foundDatetime, twoMonthsAgo),
      ),
    );

  const [totalThisMonthCount] = await db
    .select({ count: count() })
    .from(foundItemsTable)
    .where(gte(foundItemsTable.foundDatetime, monthStart));

  const [returnedThisMonthCount] = await db
    .select({ count: count() })
    .from(foundItemsTable)
    .where(
      and(
        eq(foundItemsTable.status, "返還済"),
        gte(foundItemsTable.returnedAt, monthStart),
      ),
    );

  const [activeLostReportsCount] = await db
    .select({ count: count() })
    .from(lostReportsTable)
    .where(eq(lostReportsTable.status, "探索中"));

  const stats = {
    storing: storingCount.count,
    todayFound: todayFoundCount.count,
    nearExpiry: nearExpiryCount.count,
    totalThisMonth: totalThisMonthCount.count,
    returnedThisMonth: returnedThisMonthCount.count,
    activeLostReports: activeLostReportsCount.count,
    pendingMatches: 0, // computed dynamically
  };

  res.json(GetDashboardStatsResponse.parse(stats));
});

router.get("/dashboard/weekly-trend", async (req, res): Promise<void> => {
  const now = new Date();
  const days: { date: string; found: number; returned: number }[] = [];

  for (let i = 6; i >= 0; i--) {
    const day = new Date(now);
    day.setDate(day.getDate() - i);
    const dayStart = new Date(day.getFullYear(), day.getMonth(), day.getDate());
    const dayEnd = new Date(dayStart);
    dayEnd.setDate(dayEnd.getDate() + 1);

    const [foundCount] = await db
      .select({ count: count() })
      .from(foundItemsTable)
      .where(
        and(
          gte(foundItemsTable.foundDatetime, dayStart),
          lte(foundItemsTable.foundDatetime, dayEnd),
        ),
      );

    const [returnedCount] = await db
      .select({ count: count() })
      .from(foundItemsTable)
      .where(
        and(
          eq(foundItemsTable.status, "返還済"),
          gte(foundItemsTable.returnedAt, dayStart),
          lte(foundItemsTable.returnedAt, dayEnd),
        ),
      );

    days.push({
      date: dayStart.toISOString().split("T")[0],
      found: foundCount.count,
      returned: returnedCount.count,
    });
  }

  res.json(GetDashboardWeeklyTrendResponse.parse(days));
});

export default router;
