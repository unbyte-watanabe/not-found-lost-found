import { Router, type IRouter } from "express";
import path from "path";
import fs from "fs";
import { openai } from "@workspace/integrations-openai-ai-server";

const router: IRouter = Router();

const CATEGORIES = ["財布・カバン類", "衣類", "電子機器", "傘", "その他"] as const;
type Category = (typeof CATEGORIES)[number];

router.post("/analyze-image", async (req, res): Promise<void> => {
  const { imageUrl } = req.body as { imageUrl?: string };

  if (!imageUrl) {
    res.status(400).json({ error: "imageUrl is required" });
    return;
  }

  // Resolve the image — either a local upload path or an external URL
  let imageData: string;
  let mediaType = "image/jpeg";

  const localMatch = imageUrl.match(/^\/api\/uploads\/(.+)$/);
  if (localMatch) {
    const filename = path.basename(localMatch[1]);
    const filePath = path.join(process.cwd(), "uploads", filename);
    if (!fs.existsSync(filePath)) {
      res.status(404).json({ error: "Image file not found" });
      return;
    }
    const ext = path.extname(filename).toLowerCase();
    if (ext === ".png") mediaType = "image/png";
    else if (ext === ".webp") mediaType = "image/webp";
    else if (ext === ".gif") mediaType = "image/gif";
    const buffer = fs.readFileSync(filePath);
    imageData = `data:${mediaType};base64,${buffer.toString("base64")}`;
  } else {
    // External URL — pass directly
    imageData = imageUrl;
  }

  const prompt = `あなたは遺失物・落とし物管理システムのアシスタントです。
画像に写っているアイテムを分析し、以下のJSON形式で日本語で回答してください。

{
  "category": "財布・カバン類 | 衣類 | 電子機器 | 傘 | その他 のいずれか",
  "subCategory": "具体的な種類（例: 二つ折り財布、リュックサック、スマートフォン、折りたたみ傘 など）",
  "features": "色、素材、ブランド、特徴的な点などを簡潔に記述（例: 黒い革製の二つ折り財布。カードスロット複数あり、右下にブランドロゴのような金具）"
}

JSONのみを返してください。説明文は不要です。`;

  try {
    const response = await openai.chat.completions.create({
      model: "gpt-5.4-mini",
      max_completion_tokens: 512,
      messages: [
        {
          role: "user",
          content: [
            {
              type: "image_url",
              image_url: {
                url: imageData,
                detail: "low",
              },
            },
            {
              type: "text",
              text: prompt,
            },
          ],
        },
      ],
    });

    const raw = response.choices[0]?.message?.content ?? "";

    // Strip markdown code fences if present
    const jsonStr = raw.replace(/^```(?:json)?\n?/, "").replace(/\n?```$/, "").trim();

    let parsed: { category: Category; subCategory: string; features: string };
    try {
      parsed = JSON.parse(jsonStr);
    } catch {
      res.status(422).json({ error: "AIの応答を解析できませんでした", raw });
      return;
    }

    // Validate category
    const category: Category = CATEGORIES.includes(parsed.category as Category)
      ? (parsed.category as Category)
      : "その他";

    res.json({
      category,
      subCategory: parsed.subCategory ?? "",
      features: parsed.features ?? "",
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : "Unknown error";
    res.status(500).json({ error: `AI解析に失敗しました: ${message}` });
  }
});

export default router;
