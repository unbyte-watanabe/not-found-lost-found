<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FoundItemCategory;
use App\Enums\FoundItemStatus;
use App\Models\FoundItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FoundItemSeeder extends Seeder
{
    public function run(): void
    {
        // 6 items with status 保管中 (storing)
        // Including 2 near-expiry items (found > 75 days ago)
        $storingItems = [
            [
                'management_no'    => Carbon::today()->format('Ymd') . '-0001',
                'status'           => FoundItemStatus::Storing->value,
                'category'         => FoundItemCategory::WalletBag->value,
                'sub_category'     => '財布',
                'features'         => '黒色の二つ折り財布、カード複数枚入り、現金約3000円',
                'found_datetime'   => Carbon::today()->subDays(3),
                'found_location'   => 'メインエントランス付近',
                'storage_location' => 'インフォメーションセンター 棚1',
                'finder_name'      => '山田スタッフ',
                'finder_contact'   => null,
                'rights_waived'    => false,
                'returned_at'      => null,
                'returned_to'      => null,
                'returned_by'      => null,
                'identity_verified' => false,
                'receipt_signed'   => false,
            ],
            [
                'management_no'    => Carbon::today()->format('Ymd') . '-0002',
                'status'           => FoundItemStatus::Storing->value,
                'category'         => FoundItemCategory::Electronics->value,
                'sub_category'     => 'スマートフォン',
                'features'         => 'iPhoneケース付き、黒色、画面割れなし、ロック中',
                'found_datetime'   => Carbon::today()->subDays(5),
                'found_location'   => 'フードコート内',
                'storage_location' => 'インフォメーションセンター 棚2',
                'finder_name'      => '佐藤スタッフ',
                'finder_contact'   => null,
                'rights_waived'    => false,
                'returned_at'      => null,
                'returned_to'      => null,
                'returned_by'      => null,
                'identity_verified' => false,
                'receipt_signed'   => false,
            ],
            [
                'management_no'    => Carbon::today()->format('Ymd') . '-0003',
                'status'           => FoundItemStatus::Storing->value,
                'category'         => FoundItemCategory::Umbrella->value,
                'sub_category'     => null,
                'features'         => '紺色の折り畳み傘、木製ハンドル、特徴的なストライプ柄',
                'found_datetime'   => Carbon::today()->subDays(10),
                'found_location'   => 'アトラクションA前',
                'storage_location' => 'インフォメーションセンター 棚3',
                'finder_name'      => null,
                'finder_contact'   => null,
                'rights_waived'    => true,
                'returned_at'      => null,
                'returned_to'      => null,
                'returned_by'      => null,
                'identity_verified' => false,
                'receipt_signed'   => false,
            ],
            [
                'management_no'    => Carbon::today()->format('Ymd') . '-0004',
                'status'           => FoundItemStatus::Storing->value,
                'category'         => FoundItemCategory::Clothing->value,
                'sub_category'     => 'ジャケット',
                'features'         => '紺色のジャケット、Mサイズ、右ポケットに鍵あり、ブランドタグあり',
                'found_datetime'   => Carbon::today()->subDays(20),
                'found_location'   => 'ベンチ周辺',
                'storage_location' => 'インフォメーションセンター 棚4',
                'finder_name'      => '田中スタッフ',
                'finder_contact'   => null,
                'rights_waived'    => false,
                'returned_at'      => null,
                'returned_to'      => null,
                'returned_by'      => null,
                'identity_verified' => false,
                'receipt_signed'   => false,
            ],
            // Near-expiry item 1 (76 days ago)
            [
                'management_no'    => Carbon::today()->subDays(76)->format('Ymd') . '-0001',
                'status'           => FoundItemStatus::Storing->value,
                'category'         => FoundItemCategory::Other->value,
                'sub_category'     => '鍵',
                'features'         => 'シルバーの鍵束、3本セット、キーホルダー付き（猫のマスコット）',
                'found_datetime'   => Carbon::today()->subDays(76),
                'found_location'   => '駐車場B出口',
                'storage_location' => 'インフォメーションセンター 棚5',
                'finder_name'      => '鈴木スタッフ',
                'finder_contact'   => null,
                'rights_waived'    => false,
                'returned_at'      => null,
                'returned_to'      => null,
                'returned_by'      => null,
                'identity_verified' => false,
                'receipt_signed'   => false,
            ],
            // Near-expiry item 2 (80 days ago)
            [
                'management_no'    => Carbon::today()->subDays(80)->format('Ymd') . '-0001',
                'status'           => FoundItemStatus::Storing->value,
                'category'         => FoundItemCategory::WalletBag->value,
                'sub_category'     => 'カバン',
                'features'         => '赤色のショルダーバッグ、レザー製、内側に名刺入りあり',
                'found_datetime'   => Carbon::today()->subDays(80),
                'found_location'   => 'ショップ前',
                'storage_location' => 'インフォメーションセンター 棚6',
                'finder_name'      => '伊藤スタッフ',
                'finder_contact'   => null,
                'rights_waived'    => false,
                'returned_at'      => null,
                'returned_to'      => null,
                'returned_by'      => null,
                'identity_verified' => false,
                'receipt_signed'   => false,
            ],
        ];

        foreach ($storingItems as $item) {
            FoundItem::create($item);
        }

        // 2 items with status 返還済 (returned)
        $returnedItems = [
            [
                'management_no'    => Carbon::today()->subDays(15)->format('Ymd') . '-0002',
                'status'           => FoundItemStatus::Returned->value,
                'category'         => FoundItemCategory::Electronics->value,
                'sub_category'     => 'イヤホン',
                'features'         => 'AirPods Pro、白色ケース付き、右耳と左耳のセット',
                'found_datetime'   => Carbon::today()->subDays(15),
                'found_location'   => 'トイレ前廊下',
                'storage_location' => 'インフォメーションセンター 棚2',
                'finder_name'      => '渡辺スタッフ',
                'finder_contact'   => null,
                'rights_waived'    => false,
                'returned_at'      => Carbon::today()->subDays(10),
                'returned_to'      => '中村花子',
                'returned_by'      => '佐藤スタッフ',
                'identity_verified' => true,
                'receipt_signed'   => true,
            ],
            [
                'management_no'    => Carbon::today()->subDays(25)->format('Ymd') . '-0001',
                'status'           => FoundItemStatus::Returned->value,
                'category'         => FoundItemCategory::WalletBag->value,
                'sub_category'     => '財布',
                'features'         => '茶色のブランド長財布、クレジットカード複数枚、免許証入り',
                'found_datetime'   => Carbon::today()->subDays(25),
                'found_location'   => 'インフォメーションセンター',
                'storage_location' => 'インフォメーションセンター 棚1',
                'finder_name'      => '小林スタッフ',
                'finder_contact'   => null,
                'rights_waived'    => false,
                'returned_at'      => Carbon::today()->subDays(22),
                'returned_to'      => '加藤太郎',
                'returned_by'      => '山田スタッフ',
                'identity_verified' => true,
                'receipt_signed'   => true,
            ],
        ];

        foreach ($returnedItems as $item) {
            FoundItem::create($item);
        }

        // 1 item with status 警察提出済 (police submitted)
        FoundItem::create([
            'management_no'    => Carbon::today()->subDays(50)->format('Ymd') . '-0001',
            'status'           => FoundItemStatus::PoliceSubmitted->value,
            'category'         => FoundItemCategory::Other->value,
            'sub_category'     => 'メガネ',
            'features'         => '黒縁メガネ、ハードケース付き、度入りレンズ、ハードケースは青色',
            'found_datetime'   => Carbon::today()->subDays(50),
            'found_location'   => '乗り物乗降口付近',
            'storage_location' => '警察署提出済',
            'finder_name'      => '吉田スタッフ',
            'finder_contact'   => null,
            'rights_waived'    => false,
            'returned_at'      => null,
            'returned_to'      => null,
            'returned_by'      => null,
            'identity_verified' => false,
            'receipt_signed'   => false,
        ]);

        // 1 item with status 期間満了処分 (disposed)
        FoundItem::create([
            'management_no'    => Carbon::today()->subDays(100)->format('Ymd') . '-0001',
            'status'           => FoundItemStatus::Disposed->value,
            'category'         => FoundItemCategory::Umbrella->value,
            'sub_category'     => null,
            'features'         => '黄色の長傘、子供用、キャラクター柄、持ち手に名前シールあり',
            'found_datetime'   => Carbon::today()->subDays(100),
            'found_location'   => 'メインエントランス付近',
            'storage_location' => '処分済',
            'finder_name'      => null,
            'finder_contact'   => null,
            'rights_waived'    => true,
            'returned_at'      => null,
            'returned_to'      => null,
            'returned_by'      => null,
            'identity_verified' => false,
            'receipt_signed'   => false,
        ]);
    }
}
