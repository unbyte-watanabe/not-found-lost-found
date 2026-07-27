<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FoundItemCategory;
use App\Enums\FoundItemStatus;
use App\Models\FoundItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FoundItem>
 */
class FoundItemFactory extends Factory
{
    protected $model = FoundItem::class;

    private static array $featuresByCategory = [
        '財布・カバン類' => [
            '黒色の二つ折り財布、カード多数入り',
            'ブランドのバッグ、茶色、内側に名刺あり',
            '青色のリュックサック、ノートPC入り',
            '赤色の長財布、免許証入り',
            '女性用ハンドバッグ、ベージュ色',
        ],
        '衣類' => [
            '紺色のジャケット、Mサイズ、右ポケットに鍵あり',
            '黄色のレインコート、子供用',
            '白いTシャツ、ロゴプリント入り、Lサイズ',
            '灰色のパーカー、フード付き',
            '赤いマフラー、ウール素材',
        ],
        '電子機器' => [
            'iPhoneケース付き、画面割れなし',
            'イヤホン、黒色、ケース付き',
            'モバイルバッテリー、白色',
            'スマートウォッチ、シルバーカラー',
            'ワイヤレスイヤホン、右耳のみ',
        ],
        '傘' => [
            '黒色の折り畳み傘、木製ハンドル',
            '水色の長傘、花柄模様',
            '透明のビニール傘',
            '紺色の傘、ストライプ柄',
            '赤色の子供用傘、キャラクター柄',
        ],
        'その他' => [
            '銀色の鍵束、3本セット',
            '定期入れ、黒色、Suicaカード入り',
            'メガネ、黒フレーム、度入り',
            '帽子、野球帽、グレー色',
            '本、文庫本、しおりあり',
        ],
    ];

    private static array $locations = [
        'メインエントランス付近',
        'アトラクションA前',
        'フードコート内',
        'トイレ前廊下',
        'ベンチ周辺',
        '駐車場B出口',
        'ショップ前',
        'ゴミ箱付近',
        'インフォメーションセンター',
        '乗り物乗降口付近',
    ];

    public function definition(): array
    {
        $categoryEnum  = fake()->randomElement(FoundItemCategory::cases());
        $categoryValue = $categoryEnum->value;
        $featuresList  = self::$featuresByCategory[$categoryValue] ?? ['詳細不明の落とし物'];

        $foundDatetime = fake()->dateTimeBetween('-90 days', 'now');
        $status        = fake()->randomElement(FoundItemStatus::cases());

        $returnedAt = null;
        $returnedTo = null;
        $returnedBy = null;
        $identityVerified = false;
        $receiptSigned = false;

        if ($status === FoundItemStatus::Returned) {
            $returnedAt       = Carbon::instance($foundDatetime)->addDays(fake()->numberBetween(1, 14));
            $returnedTo       = fake()->name();
            $returnedBy       = fake()->name();
            $identityVerified = true;
            $receiptSigned    = true;
        }

        return [
            'management_no'   => date('Ymd', $foundDatetime->getTimestamp()) . '-' . str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status'          => $status->value,
            'category'        => $categoryValue,
            'sub_category'    => fake()->optional(0.4)->randomElement(['財布', 'カバン', 'スマートフォン', 'キー', 'アパレル']),
            'features'        => fake()->randomElement($featuresList),
            'found_datetime'  => $foundDatetime,
            'found_location'  => fake()->randomElement(self::$locations),
            'image_url'       => null,
            'storage_location' => 'インフォメーションセンター 棚' . fake()->numberBetween(1, 10),
            'finder_name'     => fake()->optional(0.6)->name(),
            'finder_contact'  => fake()->optional(0.4)->phoneNumber(),
            'rights_waived'   => fake()->boolean(30),
            'returned_at'     => $returnedAt,
            'returned_to'     => $returnedTo,
            'returned_by'     => $returnedBy,
            'identity_verified' => $identityVerified,
            'receipt_signed'  => $receiptSigned,
        ];
    }

    public function storing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => FoundItemStatus::Storing->value,
            'returned_at' => null,
            'returned_to' => null,
            'returned_by' => null,
        ]);
    }

    public function returned(): static
    {
        return $this->state(function (array $attributes) {
            $foundDatetime = Carbon::parse($attributes['found_datetime'] ?? now()->subDays(7));

            return [
                'status'            => FoundItemStatus::Returned->value,
                'returned_at'       => $foundDatetime->addDays(fake()->numberBetween(1, 14)),
                'returned_to'       => fake()->name(),
                'returned_by'       => fake()->name(),
                'identity_verified' => true,
                'receipt_signed'    => true,
            ];
        });
    }

    public function policeSubmitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FoundItemStatus::PoliceSubmitted->value,
        ]);
    }

    public function disposed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FoundItemStatus::Disposed->value,
        ]);
    }

    public function nearExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'         => FoundItemStatus::Storing->value,
            'found_datetime' => fake()->dateTimeBetween('-90 days', '-76 days'),
        ]);
    }
}
