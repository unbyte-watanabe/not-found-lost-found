import { pgTable, text, uuid, timestamp, pgEnum } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod/v4";

export const lostReportStatusEnum = pgEnum("lost_report_status", [
  "探索中",
  "解決済",
  "キャンセル",
]);

export const lostReportsTable = pgTable("lost_reports", {
  id: uuid("id").primaryKey().defaultRandom(),
  status: lostReportStatusEnum("status").notNull().default("探索中"),
  ownerName: text("owner_name").notNull(),
  ownerContact: text("owner_contact").notNull(),
  lostDatetimeFrom: timestamp("lost_datetime_from", { withTimezone: true }),
  lostDatetimeTo: timestamp("lost_datetime_to", { withTimezone: true }),
  lostLocationEstimated: text("lost_location_estimated"),
  category: text("category").notNull(),
  features: text("features"),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow().$onUpdate(() => new Date()),
});

export const insertLostReportSchema = createInsertSchema(lostReportsTable).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export type InsertLostReport = z.infer<typeof insertLostReportSchema>;
export type LostReport = typeof lostReportsTable.$inferSelect;
