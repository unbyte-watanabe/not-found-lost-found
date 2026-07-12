import { Router, type IRouter } from "express";
import { db, foundItemsTable, lostReportsTable } from "@workspace/db";
import { eq, and, ilike, or, desc } from "drizzle-orm";
import {
  CreateLostReportBody,
  UpdateLostReportBody,
  UpdateLostReportStatusBody,
  GetLostReportParams,
  UpdateLostReportParams,
  UpdateLostReportStatusParams,
  ListLostReportsQueryParams,
  GetLostReportResponse,
  UpdateLostReportResponse,
  UpdateLostReportStatusResponse,
  ListLostReportsResponse,
  CreateLostReportResponse,
} from "@workspace/api-zod";
import { computeMatchScore } from "../lib/matching";

const router: IRouter = Router();

router.get("/lost-reports", async (req, res): Promise<void> => {
  const parsed = ListLostReportsQueryParams.safeParse(req.query);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const { status, category, search, page, limit } = parsed.data;
  const offset = ((page ?? 1) - 1) * (limit ?? 20);

  const conditions = [];

  if (status) conditions.push(eq(lostReportsTable.status, status as "探索中" | "解決済" | "キャンセル"));
  if (category) conditions.push(eq(lostReportsTable.category, category));
  if (search) {
    conditions.push(
      or(
        ilike(lostReportsTable.features, `%${search}%`),
        ilike(lostReportsTable.ownerName, `%${search}%`),
        ilike(lostReportsTable.lostLocationEstimated, `%${search}%`),
      ),
    );
  }

  const whereClause = conditions.length > 0 ? and(...conditions) : undefined;

  const items = await db
    .select()
    .from(lostReportsTable)
    .where(whereClause)
    .orderBy(desc(lostReportsTable.createdAt))
    .limit(limit ?? 20)
    .offset(offset);

  const [totalResult] = await db
    .select({ count: db.$count(lostReportsTable, whereClause) })
    .from(lostReportsTable)
    .where(whereClause);

  res.json(
    ListLostReportsResponse.parse({
      items,
      total: Number(totalResult?.count ?? items.length),
      page: page ?? 1,
      limit: limit ?? 20,
    }),
  );
});

router.post("/lost-reports", async (req, res): Promise<void> => {
  const parsed = CreateLostReportBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const [report] = await db
    .insert(lostReportsTable)
    .values({
      ...parsed.data,
      lostDatetimeFrom: parsed.data.lostDatetimeFrom
        ? new Date(parsed.data.lostDatetimeFrom)
        : undefined,
      lostDatetimeTo: parsed.data.lostDatetimeTo
        ? new Date(parsed.data.lostDatetimeTo)
        : undefined,
    })
    .returning();

  // Compute matches for the new report
  const storingItems = await db
    .select()
    .from(foundItemsTable)
    .where(eq(foundItemsTable.status, "保管中"));

  const matches = storingItems
    .map((item) => {
      const { score, reasons } = computeMatchScore(item, report);
      return { item, score, reasons };
    })
    .filter((m) => m.score >= 30)
    .sort((a, b) => b.score - a.score)
    .slice(0, 5)
    .map((m, idx) => ({
      id: `${report.id}-${idx}`,
      foundItem: m.item,
      lostReport: report,
      score: m.score,
      reasons: m.reasons,
    }));

  res.status(201).json(
    CreateLostReportResponse.parse({ report, matches }),
  );
});

router.get("/lost-reports/:id", async (req, res): Promise<void> => {
  const params = GetLostReportParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }

  const [report] = await db
    .select()
    .from(lostReportsTable)
    .where(eq(lostReportsTable.id, params.data.id));

  if (!report) {
    res.status(404).json({ error: "Lost report not found" });
    return;
  }

  res.json(GetLostReportResponse.parse(report));
});

router.put("/lost-reports/:id", async (req, res): Promise<void> => {
  const params = UpdateLostReportParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }

  const parsed = UpdateLostReportBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const updateData: Record<string, unknown> = { ...parsed.data };
  if (parsed.data.lostDatetimeFrom)
    updateData.lostDatetimeFrom = new Date(parsed.data.lostDatetimeFrom);
  if (parsed.data.lostDatetimeTo)
    updateData.lostDatetimeTo = new Date(parsed.data.lostDatetimeTo);

  const [report] = await db
    .update(lostReportsTable)
    .set(updateData)
    .where(eq(lostReportsTable.id, params.data.id))
    .returning();

  if (!report) {
    res.status(404).json({ error: "Lost report not found" });
    return;
  }

  res.json(UpdateLostReportResponse.parse(report));
});

router.patch("/lost-reports/:id/status", async (req, res): Promise<void> => {
  const params = UpdateLostReportStatusParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }

  const parsed = UpdateLostReportStatusBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const [report] = await db
    .update(lostReportsTable)
    .set({ status: parsed.data.status })
    .where(eq(lostReportsTable.id, params.data.id))
    .returning();

  if (!report) {
    res.status(404).json({ error: "Lost report not found" });
    return;
  }

  res.json(UpdateLostReportStatusResponse.parse(report));
});

export default router;
