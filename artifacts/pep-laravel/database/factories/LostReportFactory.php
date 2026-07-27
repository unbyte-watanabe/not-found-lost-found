<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FoundItemCategory;
use App\Enums\LostReportStatus;
use App\Models\LostReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LostReport>
 */
class LostReportFactory extends Factory
{
    protected $model = LostReport::class;

    private static array $featuresByCategory = [
        '財布・カバン類' => [
            '黒色の二つ折り財布、中に運転免許証と現金',
            '茶色のレザー長財布、カード多数',
            '紺色のショルダーバッグ、書類入り',
            'ベージュのトートバッグ、女性用',
        ],
        '衣類' => [
            '黒色のジャケット、Lサイズ',
            '赤いパーカー、フード付き、子供用',
            'グレーのコート、ウール素材',
        ],
        '電子機器' => [
            'iPhoneケース付き、黒色',
            'AirPods Pro、白いケース付き',
            '黒色のスマートウォッチ',
        ],
        '傘' => [
            '紺色の折り畳み傘',
            '透明のビニール傘、持ち手に名前あり',
        ],
        'その他' => [
            'シルバーの鍵2本、キーホルダー付き',
            '黒縁メガネ、ハードケース付き',
            'ICカード入り定期入れ、紺色',
        ],
    ];

    private static array $locations = [
        'メインエントランス付近',
        'アトラクションB周辺',
        'フードコートエリア',
        'レストラン前',
        'ショップ内',
        '駐車場C付近',
        'ベンチ周辺',
    ];

    private static array $ownerNames = [
        '田中太郎', '鈴木花子', '佐藤一郎', '山田優子', '伊藤健二',
        '渡辺さくら', '中村大介', '小林美咲', '加藤浩之', '吉田奈々',
    ];

    private static array $contactNumbers = [
        '090-1234-5678', '080-9876-5432', '070-1111-2222',
        '090-3333-4444', '080-5555-6666', '070-7777-8888',
    ];

    public function definition(): array
    {
        $categoryEnum  = fake()->randomElement(FoundItemCategory::cases());
        $categoryValue = $categoryEnum->value;
        $featuresList  = self::$featuresByCategory[$categoryValue] ?? ['詳細不明の落とし物'];

        $lostFrom = fake()->optional(0.8)->dateTimeBetween('-30 days', '-1 day');
        $lostTo   = null;

        if ($lostFrom !== null && fake()->boolean(50)) {
            $lostTo = Carbon::instance($lostFrom)->addHours(fake()->numberBetween(1, 8));
        }

        return [
            'status'                  => fake()->randomElement(LostReportStatus::cases())->value,
            'owner_name'              => fake()->randomElement(self::$ownerNames),
            'owner_contact'           => fake()->randomElement(self::$contactNumbers),
            'lost_datetime_from'      => $lostFrom,
            'lost_datetime_to'        => $lostTo,
            'lost_location_estimated' => fake()->optional(0.7)->randomElement(self::$locations),
            'category'                => $categoryValue,
            'features'                => fake()->randomElement($featuresList),
        ];
    }

    public function searching(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LostReportStatus::Searching->value,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LostReportStatus::Resolved->value,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LostReportStatus::Cancelled->value,
        ]);
    }
}
