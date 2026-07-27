<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FoundItemCategory;
use App\Enums\LostReportStatus;
use App\Models\LostReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LostReportSeeder extends Seeder
{
    public function run(): void
    {
        // 3 reports with status 探索中 (searching)
        $searchingReports = [
            [
                'status'                  => LostReportStatus::Searching->value,
                'owner_name'              => '田中太郎',
                'owner_contact'           => '090-1234-5678',
                'lost_datetime_from'      => Carbon::today()->subDays(3)->setHour(14)->setMinute(0),
                'lost_datetime_to'        => Carbon::today()->subDays(3)->setHour(17)->setMinute(0),
                'lost_location_estimated' => 'メインエントランス付近',
                'category'                => FoundItemCategory::WalletBag->value,
                'features'                => '黒色の二つ折り財布、カード複数枚入り、現金約5000円、運転免許証入り',
            ],
            [
                'status'                  => LostReportStatus::Searching->value,
                'owner_name'              => '鈴木花子',
                'owner_contact'           => '080-9876-5432',
                'lost_datetime_from'      => Carbon::today()->subDays(5)->setHour(10)->setMinute(0),
                'lost_datetime_to'        => Carbon::today()->subDays(5)->setHour(12)->setMinute(0),
                'lost_location_estimated' => 'フードコートエリア',
                'category'                => FoundItemCategory::Electronics->value,
                'features'                => 'AirPods Pro、白色ケース付き、右耳と左耳セット、ケースに傷なし',
            ],
            [
                'status'                  => LostReportStatus::Searching->value,
                'owner_name'              => '佐藤一郎',
                'owner_contact'           => '070-1111-2222',
                'lost_datetime_from'      => Carbon::today()->subDays(1)->setHour(13)->setMinute(30),
                'lost_datetime_to'        => null,
                'lost_location_estimated' => 'アトラクションB周辺',
                'category'                => FoundItemCategory::Other->value,
                'features'                => '黒縁メガネ、ハードケース付き、度数が強め、ケースは青色',
            ],
        ];

        foreach ($searchingReports as $report) {
            LostReport::create($report);
        }

        // 1 report with status 解決済 (resolved)
        LostReport::create([
            'status'                  => LostReportStatus::Resolved->value,
            'owner_name'              => '山田優子',
            'owner_contact'           => '090-3333-4444',
            'lost_datetime_from'      => Carbon::today()->subDays(20)->setHour(15)->setMinute(0),
            'lost_datetime_to'        => Carbon::today()->subDays(20)->setHour(16)->setMinute(0),
            'lost_location_estimated' => 'ショップ内',
            'category'                => FoundItemCategory::WalletBag->value,
            'features'                => '茶色のレザー長財布、ブランドロゴあり、クレジットカード複数枚入り',
        ]);

        // 1 report with status キャンセル (cancelled)
        LostReport::create([
            'status'                  => LostReportStatus::Cancelled->value,
            'owner_name'              => '伊藤健二',
            'owner_contact'           => '080-5555-6666',
            'lost_datetime_from'      => Carbon::today()->subDays(10)->setHour(11)->setMinute(0),
            'lost_datetime_to'        => null,
            'lost_location_estimated' => null,
            'category'                => FoundItemCategory::Umbrella->value,
            'features'                => '紺色の折り畳み傘、木製ハンドル、ストライプ柄、購入から1年程度',
        ]);
    }
}
