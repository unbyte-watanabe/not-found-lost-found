<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FoundItem;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    private ExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExportService();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeItem(array $attrs = []): FoundItem
    {
        $item = new FoundItem();
        $item->management_no    = $attrs['management_no']    ?? '20240115-0001';
        $item->status           = $attrs['status']           ?? '保管中';
        $item->category         = $attrs['category']         ?? '財布・カバン類';
        $item->sub_category     = $attrs['sub_category']     ?? '財布';
        $item->features         = $attrs['features']         ?? '黒色の二つ折り財布';
        $item->found_datetime   = $attrs['found_datetime']   ?? Carbon::parse('2024-01-15 10:30:00');
        $item->found_location   = $attrs['found_location']   ?? 'メインエントランス';
        $item->storage_location = $attrs['storage_location'] ?? '棚A-1';
        $item->finder_name      = $attrs['finder_name']      ?? '田中太郎';
        $item->finder_contact   = $attrs['finder_contact']   ?? '090-1234-5678';
        $item->rights_waived    = $attrs['rights_waived']    ?? false;
        $item->created_at       = $attrs['created_at']       ?? Carbon::parse('2024-01-15 11:00:00');
        return $item;
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_generatePoliceCsv_starts_with_utf8_bom(): void
    {
        $csv = $this->service->generatePoliceCsv(new Collection([]));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_generatePoliceCsv_contains_correct_csv_headers_in_first_data_line(): void
    {
        $csv   = $this->service->generatePoliceCsv(new Collection([]));
        // Strip BOM
        $body  = substr($csv, 3);
        $lines = explode("\n", trim($body));
        $firstLine = $lines[0];

        $expectedHeaders = [
            '管理番号',
            'ステータス',
            'カテゴリ',
            'サブカテゴリ',
            '特徴',
            '拾得日時',
            '拾得場所',
            '保管場所',
            '拾得者氏名',
            '連絡先',
            '権利放棄',
            '登録日',
        ];

        foreach ($expectedHeaders as $header) {
            $this->assertStringContainsString($header, $firstLine);
        }
    }

    public function test_each_item_becomes_a_row_with_correct_column_count(): void
    {
        $item1 = $this->makeItem(['management_no' => '20240115-0001']);
        $item2 = $this->makeItem(['management_no' => '20240115-0002']);

        $csv  = $this->service->generatePoliceCsv(new Collection([$item1, $item2]));
        $body = substr($csv, 3); // remove BOM

        $lines    = array_filter(explode("\n", trim($body)));
        $linesArr = array_values($lines);

        // Header + 2 data rows
        $this->assertCount(3, $linesArr);

        // Each data row has 12 columns
        foreach (array_slice($linesArr, 1) as $dataLine) {
            $columns = str_getcsv($dataLine);
            $this->assertCount(12, $columns, "Expected 12 columns in: {$dataLine}");
        }
    }

    public function test_empty_collection_returns_bom_plus_headers_only(): void
    {
        $csv  = $this->service->generatePoliceCsv(new Collection([]));
        $body = substr($csv, 3);

        $lines = array_values(array_filter(explode("\n", trim($body))));

        $this->assertCount(1, $lines);
    }

    public function test_datetime_fields_are_formatted_as_Y_m_d_H_i(): void
    {
        $foundDt   = Carbon::parse('2024-01-15 10:30:00');
        $createdDt = Carbon::parse('2024-01-15 11:00:00');

        $item = $this->makeItem([
            'found_datetime' => $foundDt,
            'created_at'     => $createdDt,
        ]);

        $csv = $this->service->generatePoliceCsv(new Collection([$item]));

        $this->assertStringContainsString('2024/01/15 10:30', $csv);
        $this->assertStringContainsString('2024/01/15 11:00', $csv);
    }

    public function test_rights_waived_true_maps_to_放棄済(): void
    {
        $item = $this->makeItem(['rights_waived' => true]);

        $csv = $this->service->generatePoliceCsv(new Collection([$item]));

        $this->assertStringContainsString('放棄済', $csv);
    }

    public function test_rights_waived_false_maps_to_未放棄(): void
    {
        $item = $this->makeItem(['rights_waived' => false]);

        $csv = $this->service->generatePoliceCsv(new Collection([$item]));

        $this->assertStringContainsString('未放棄', $csv);
    }

    public function test_generatePoliceCsv_handles_null_datetime_gracefully(): void
    {
        $item = $this->makeItem([
            'found_datetime' => null,
            'created_at'     => null,
        ]);

        $csv = $this->service->generatePoliceCsv(new Collection([$item]));

        // Should not throw; BOM is still present
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_generatePoliceCsv_includes_management_number_in_output(): void
    {
        $item = $this->makeItem(['management_no' => '20240999-0042']);

        $csv = $this->service->generatePoliceCsv(new Collection([$item]));

        $this->assertStringContainsString('20240999-0042', $csv);
    }
}
