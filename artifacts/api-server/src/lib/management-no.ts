import { db, foundItemsTable } from "@workspace/db";
import { like, count } from "drizzle-orm";

/**
 * Generate a management number in the format YYYYMMDD-XXXX
 */
export async function generateManagementNo(): Promise<string> {
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth() + 1).padStart(2, "0");
  const dd = String(now.getDate()).padStart(2, "0");
  const dateStr = `${yyyy}${mm}${dd}`;

  const prefix = `${dateStr}-`;
  const [result] = await db
    .select({ count: count() })
    .from(foundItemsTable)
    .where(like(foundItemsTable.managementNo, `${prefix}%`));

  const seq = (result.count + 1).toString().padStart(4, "0");
  return `${prefix}${seq}`;
}
