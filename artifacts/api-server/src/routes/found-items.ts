import { Router, type IRouter } from "express";
import { db, foundItemsTable } from "@workspace/db";
import { eq, and, gte, lte, ilike, or, desc } from "drizzle-orm";
import {
  CreateFoundItemBody,
  UpdateFoundItemBody,
  UpdateFoundItemStatusBody,
  GetFoundItemParams,
  UpdateFoundItemParams,
  DeleteFoundItemParams,
  UpdateFoundItemStatusParams,
  ListFoundItemsQueryParams,
  ExportFoundItemsForPoliceQueryParams,
  GetFoundItemResponse,
  ListFoundItemsResponse,
  UpdateFoundItemResponse,
  UpdateFoundItemStatusResponse,
  CreateFoundItemResponse,
} from "@workspace/api-zod";
import { generateManagementNo } from "../lib/management-no";

const router: IRouter = Router();

router.get("/found-items", async (req, res): Promise<void> => {
  const parsed = ListFoundItemsQueryParams.safeParse(req.query);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const { status, category, dateFrom, dateTo, search, page, limit } =
    parsed.data;
  const offset = ((page ?? 1) - 1) * (limit ?? 20);

  const conditions = [];

  if (status) conditions.push(eq(foundItemsTable.status, status as "保管中" | "返還済" | "警察提出済" | "期間満了処分"));
  if (category) conditions.push(eq(foundItemsTable.category, category as "財布・カバン類" | "衣類" | "電子機器" | "傘" | "その他"));
  if (dateFrom) conditions.push(gte(foundItemsTable.foundDatetime, new Date(dateFrom)));
  if (dateTo) {
    const end = new Date(dateTo);
    end.setDate(end.getDate() + 1);
    conditions.push(lte(foundItemsTable.foundDatetime, end));
  }
  if (search) {
    conditions.push(
      or(
        ilike(foundItemsTable.features, `%${search}%`),
        ilike(foundItemsTable.subCategory, `%${search}%`),
        ilike(foundItemsTable.foundLocation, `%${search}%`),
        ilike(foundItemsTable.storageLocation, `%${search}%`),
        ilike(foundItemsTable.managementNo, `%${search}%`),
      ),
    );
  }

  const whereClause = conditions.length > 0 ? and(...conditions) : undefined;

  const items = await db
    .select()
    .from(foundItemsTable)
    .where(whereClause)
    .orderBy(desc(foundItemsTable.foundDatetime))
    .limit(limit ?? 20)
    .offset(offset);

  const [totalResult] = await db
    .select({ count: db.$count(foundItemsTable, whereClause) })
    .from(foundItemsTable)
    .where(whereClause);

  res.json(
    ListFoundItemsResponse.parse({
      items,
      total: Number(totalResult?.count ?? items.length),
      page: page ?? 1,
      limit: limit ?? 20,
    }),
  );
});

router.post("/found-items", async (req, res): Promise<void> => {
  const parsed = CreateFoundItemBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const managementNo = await generateManagementNo();

  const [item] = await db
    .insert(foundItemsTable)
    .values({
      ...parsed.data,
      managementNo,
      foundDatetime: new Date(parsed.data.foundDatetime),
    })
    .returning();

  res.status(201).json(CreateFoundItemResponse.parse(item));
});

// Police export route — must come before /:id routes
router.get("/found-items/export/police", async (req, res): Promise<void> => {
  const parsed = ExportFoundItemsForPoliceQueryParams.safeParse(req.query);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const { dateFrom, dateTo } = parsed.data;

  const conditions = [eq(foundItemsTable.status, "保管中")];
  if (dateFrom) conditions.push(gte(foundItemsTable.foundDatetime, new Date(dateFrom)));
  if (dateTo) {
    const end = new Date(dateTo);
    end.setDate(end.getDate() + 1);
    conditions.push(lte(foundItemsTable.foundDatetime, end));
  }

  const items = await db
    .select()
    .from(foundItemsTable)
    .where(and(...conditions))
    .orderBy(foundItemsTable.managementNo);

  const header = [
    "管理番号",
    "拾得日時",
    "拾得場所",
    "大分類",
    "小分類",
    "特徴",
    "保管場所",
    "拾得者氏名",
    "拾得者連絡先",
    "権利放棄",
    "登録日時",
  ].join(",");

  const rows = items.map((item) =>
    [
      item.managementNo,
      item.foundDatetime.toISOString(),
      item.foundLocation ?? "",
      item.category,
      item.subCategory ?? "",
      `"${(item.features ?? "").replace(/"/g, '""')}"`,
      item.storageLocation ?? "",
      (item.finderInfo as Record<string, unknown>)?.name ?? "",
      (item.finderInfo as Record<string, unknown>)?.contact ?? "",
      (item.finderInfo as Record<string, unknown>)?.rightsWaived ? "有" : "無",
      item.createdAt.toISOString(),
    ].join(","),
  );

  const csv = [header, ...rows].join("\n");
  const bom = "\uFEFF"; // UTF-8 BOM for Excel compatibility

  res.setHeader("Content-Type", "text/csv; charset=utf-8");
  res.setHeader(
    "Content-Disposition",
    `attachment; filename="pep_lost_found_${new Date().toISOString().split("T")[0]}.csv"`,
  );
  res.send(bom + csv);
});

router.get("/found-items/:id", async (req, res): Promise<void> => {
  const params = GetFoundItemParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }

  const [item] = await db
    .select()
    .from(foundItemsTable)
    .where(eq(foundItemsTable.id, params.data.id));

  if (!item) {
    res.status(404).json({ error: "Found item not found" });
    return;
  }

  res.json(GetFoundItemResponse.parse(item));
});

router.put("/found-items/:id", async (req, res): Promise<void> => {
  const params = UpdateFoundItemParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }

  const parsed = UpdateFoundItemBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const updateData: Record<string, unknown> = { ...parsed.data };
  if (parsed.data.foundDatetime) {
    updateData.foundDatetime = new Date(parsed.data.foundDatetime);
  }

  const [item] = await db
    .update(foundItemsTable)
    .set(updateData)
    .where(eq(foundItemsTable.id, params.data.id))
    .returning();

  if (!item) {
    res.status(404).json({ error: "Found item not found" });
    return;
  }

  res.json(UpdateFoundItemResponse.parse(item));
});

router.delete("/found-items/:id", async (req, res): Promise<void> => {
  const params = DeleteFoundItemParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }

  const [item] = await db
    .delete(foundItemsTable)
    .where(eq(foundItemsTable.id, params.data.id))
    .returning();

  if (!item) {
    res.status(404).json({ error: "Found item not found" });
    return;
  }

  res.sendStatus(204);
});

router.patch("/found-items/:id/status", async (req, res): Promise<void> => {
  const params = UpdateFoundItemStatusParams.safeParse(req.params);
  if (!params.success) {
    res.status(400).json({ error: params.error.message });
    return;
  }

  const parsed = UpdateFoundItemStatusBody.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.message });
    return;
  }

  const updateData: Record<string, unknown> = {
    status: parsed.data.status,
  };

  if (parsed.data.status === "返還済") {
    updateData.returnedAt = new Date();
    if (parsed.data.returnedTo) updateData.returnedTo = parsed.data.returnedTo;
    if (parsed.data.returnedBy) updateData.returnedBy = parsed.data.returnedBy;
    if (parsed.data.identityVerified !== undefined)
      updateData.identityVerified = parsed.data.identityVerified;
    if (parsed.data.receiptSigned !== undefined)
      updateData.receiptSigned = parsed.data.receiptSigned;
  }

  const [item] = await db
    .update(foundItemsTable)
    .set(updateData)
    .where(eq(foundItemsTable.id, params.data.id))
    .returning();

  if (!item) {
    res.status(404).json({ error: "Found item not found" });
    return;
  }

  res.json(UpdateFoundItemStatusResponse.parse(item));
});

export default router;
