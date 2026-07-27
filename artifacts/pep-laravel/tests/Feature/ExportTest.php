<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FoundItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    // ── Web — police form ─────────────────────────────────────────────────────

    public function test_get_export_police_returns_200(): void
    {
        $response = $this->get('/export/police');

        $response->assertStatus(200);
    }

    // ── Web — CSV download ────────────────────────────────────────────────────

    public function test_get_export_police_download_returns_200_with_csv_content_type(): void
    {
        FoundItem::factory()->count(2)->create();

        $response = $this->get('/export/police/download');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_get_export_police_download_with_date_range_returns_200_csv(): void
    {
        FoundItem::factory()->create([
            'found_datetime' => '2024-06-15 10:00:00',
        ]);

        $response = $this->get('/export/police/download?dateFrom=2024-01-01&dateTo=2024-12-31');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    // ── CSV content ───────────────────────────────────────────────────────────

    public function test_csv_download_contains_utf8_bom_at_start(): void
    {
        $response = $this->get('/export/police/download');

        $content = $response->getContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_csv_download_contains_correct_headers(): void
    {
        $response = $this->get('/export/police/download');

        $content  = $response->getContent();
        // Strip BOM before checking headers
        $body     = substr($content, 3);
        $lines    = explode("\n", trim($body));
        $firstLine = $lines[0];

        $expectedHeaders = [
            '管理番号',
            'ステータス',
            'カテゴリ',
            '特徴',
            '拾得日時',
            '権利放棄',
            '登録日',
        ];

        foreach ($expectedHeaders as $header) {
            $this->assertStringContainsString($header, $firstLine);
        }
    }

    public function test_csv_filename_in_content_disposition_header(): void
    {
        $response = $this->get('/export/police/download');

        $contentDisposition = $response->headers->get('Content-Disposition');

        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }

    public function test_csv_includes_item_data_when_items_exist(): void
    {
        FoundItem::factory()->create([
            'management_no'  => '20240115-0001',
            'category'       => '財布・カバン類',
            'features'       => 'テスト用の財布',
            'found_datetime' => '2024-01-15 10:30:00',
        ]);

        $response = $this->get('/export/police/download');

        $content = $response->getContent();

        $this->assertStringContainsString('20240115-0001', $content);
        $this->assertStringContainsString('テスト用の財布', $content);
    }

    public function test_csv_respects_date_filter_from_and_to(): void
    {
        // Item within range
        FoundItem::factory()->create([
            'management_no'  => '20240601-0001',
            'found_datetime' => '2024-06-01 09:00:00',
        ]);
        // Item outside range
        FoundItem::factory()->create([
            'management_no'  => '20240201-0001',
            'found_datetime' => '2024-02-01 09:00:00',
        ]);

        $response = $this->get('/export/police/download?dateFrom=2024-06-01&dateTo=2024-06-30');

        $content = $response->getContent();

        $this->assertStringContainsString('20240601-0001', $content);
        $this->assertStringNotContainsString('20240201-0001', $content);
    }

    // ── API — police export ───────────────────────────────────────────────────

    public function test_api_get_export_police_returns_200_with_csv_content_type(): void
    {
        FoundItem::factory()->count(2)->create();

        $response = $this->get('/api/found-items/export/police');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_api_export_csv_contains_bom(): void
    {
        $response = $this->get('/api/found-items/export/police');

        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->getContent());
    }
}
