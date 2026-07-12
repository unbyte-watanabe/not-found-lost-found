import { pgTable, text, uuid, timestamp, boolean, jsonb, pgEnum } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod/v4";

export const foundItemStatusEnum = pgEnum("found_item_status", [
  "保管中",
  "返還済",
  "警察提出済",
  "期間満了処分",
]);

export const itemCategoryEnum = pgEnum("item_category", [
  "財布・カバン類",
  "衣類",
  "電子機器",
  "傘",
  "その他",
]);

export const foundItemsTable = pgTable("found_items", {
  id: uuid("id").primaryKey().defaultRandom(),
  managementNo: text("management_no").notNull().unique(),
  status: foundItemStatusEnum("status").notNull().default("保管中"),
  category: itemCategoryEnum("category").notNull(),
  subCategory: text("sub_category"),
  features: text("features"),
  foundDatetime: timestamp("found_datetime", { withTimezone: true }).notNull(),
  foundLocation: text("found_location"),
  imageUrl: text("image_url"),
  storageLocation: text("storage_location"),
  finderInfo: jsonb("finder_info").$type<{
    name?: string | null;
    contact?: string | null;
    rightsWaived?: boolean;
  }>(),
  returnedAt: timestamp("returned_at", { withTimezone: true }),
  returnedTo: text("returned_to"),
  returnedBy: text("returned_by"),
  identityVerified: boolean("identity_verified").notNull().default(false),
  receiptSigned: boolean("receipt_signed").notNull().default(false),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow().$onUpdate(() => new Date()),
});

export const insertFoundItemSchema = createInsertSchema(foundItemsTable).omit({
  id: true,
  managementNo: true,
  createdAt: true,
  updatedAt: true,
});

export type InsertFoundItem = z.infer<typeof insertFoundItemSchema>;
export type FoundItem = typeof foundItemsTable.$inferSelect;
