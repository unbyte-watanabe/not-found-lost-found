<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FoundItemCategory;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Analyses an image via OpenAI GPT-4o-mini and extracts lost-item metadata.
 *
 * Returns a structured array with category, subCategory, and features
 * suitable for pre-filling a FoundItem registration form.
 */
final class AnalyzeImageService
{
    private const MODEL      = 'gpt-4o-mini';
    private const MAX_TOKENS = 512;

    /**
     * Valid category values derived from the FoundItemCategory enum.
     *
     * @var list<string>
     */
    private array $validCategories;

    public function __construct()
    {
        $this->validCategories = array_map(
            static fn (FoundItemCategory $c) => $c->value,
            FoundItemCategory::cases(),
        );
    }

    /**
     * Analyse the image at the given URL and return item metadata.
     *
     * @param string $imageUrl Publicly accessible URL of the image.
     * @return array{category: string, subCategory: string, features: string}
     *
     * @throws \RuntimeException If the API call fails or returns invalid data.
     */
    public function analyze(string $imageUrl): array
    {
        $prompt = $this->buildPrompt();

        try {
            $response = OpenAI::chat()->create([
                'model'      => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type'      => 'image_url',
                                'image_url' => ['url' => $imageUrl],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'AI画像解析APIの呼び出しに失敗しました: ' . $e->getMessage(),
                previous: $e,
            );
        }

        $content = $response->choices[0]->message->content ?? '';

        return $this->parseResponse($content);
    }

    /**
     * Build the Japanese prompt instructing the model to return JSON.
     */
    private function buildPrompt(): string
    {
        $categories = implode('、', $this->validCategories);

        return <<<PROMPT
        この画像に写っている落とし物を分析し、以下のJSON形式で回答してください。
        必ずJSON のみを返し、それ以外のテキストは含めないでください。

        {
          "category": "<カテゴリ（{$categories} のいずれか）>",
          "subCategory": "<サブカテゴリ（例: 長財布、スマートフォン など）>",
          "features": "<色・形状・ブランド・特徴などを日本語で詳細に記述>"
        }

        カテゴリは必ず上記のいずれかを選択してください。
        判断できない場合は「その他」を使用してください。
        PROMPT;
    }

    /**
     * Parse the raw model response into a structured array.
     *
     * @param string $content Raw text content from the API.
     * @return array{category: string, subCategory: string, features: string}
     *
     * @throws \RuntimeException If JSON is invalid or required fields are missing.
     */
    private function parseResponse(string $content): array
    {
        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*/u', '', trim($content));
        $cleaned = preg_replace('/\s*```$/u', '', $cleaned ?? '');

        $data = json_decode($cleaned ?? '', true);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'AI画像解析のレスポンスをJSONとして解析できませんでした。',
            );
        }

        foreach (['category', 'subCategory', 'features'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                throw new \RuntimeException(
                    sprintf('AI画像解析のレスポンスに必須フィールド「%s」が含まれていません。', $field),
                );
            }
        }

        // Validate and normalise category
        $category = $data['category'];
        if (!in_array($category, $this->validCategories, true)) {
            // Fallback to 'その他' rather than throwing, to remain user-friendly
            $category = FoundItemCategory::Other->value;
        }

        return [
            'category'    => $category,
            'subCategory' => trim($data['subCategory']),
            'features'    => trim($data['features']),
        ];
    }
}
