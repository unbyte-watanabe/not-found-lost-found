<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FoundItem;
use App\Models\LostReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LostReportTest extends TestCase
{
    use RefreshDatabase;

    // ── Web — index ───────────────────────────────────────────────────────────

    public function test_lost_reports_index_returns_200(): void
    {
        $response = $this->get('/lost-reports');

        $response->assertStatus(200);
    }

    // ── Web — create ──────────────────────────────────────────────────────────

    public function test_lost_reports_create_returns_200(): void
    {
        $response = $this->get('/lost-reports/create');

        $response->assertStatus(200);
    }

    // ── Web — store ───────────────────────────────────────────────────────────

    public function test_post_lost_reports_with_valid_data_redirects_to_show_page(): void
    {
        $response = $this->post('/lost-reports', [
            'owner_name'    => '田中太郎',
            'owner_contact' => '090-1234-5678',
            'category'      => '財布・カバン類',
            'features'      => '黒色の財布',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('lost_reports', [
            'owner_name' => '田中太郎',
            'category'   => '財布・カバン類',
        ]);

        $report = LostReport::where('owner_name', '田中太郎')->first();
        $response->assertRedirect(route('lost-reports.show', $report->id));
    }

    public function test_post_lost_reports_with_missing_fields_returns_validation_errors(): void
    {
        $response = $this->post('/lost-reports', []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['owner_name', 'owner_contact', 'category', 'features']);
    }

    // ── Web — show ────────────────────────────────────────────────────────────

    public function test_lost_reports_show_returns_200(): void
    {
        $report = LostReport::factory()->create();

        $response = $this->get("/lost-reports/{$report->id}");

        $response->assertStatus(200);
    }

    // ── Web — edit ────────────────────────────────────────────────────────────

    public function test_lost_reports_edit_returns_200(): void
    {
        $report = LostReport::factory()->create();

        $response = $this->get("/lost-reports/{$report->id}/edit");

        $response->assertStatus(200);
    }

    // ── Web — update ──────────────────────────────────────────────────────────

    public function test_put_lost_reports_with_valid_data_redirects(): void
    {
        $report = LostReport::factory()->create();

        $response = $this->put("/lost-reports/{$report->id}", [
            'owner_name'    => '更新された氏名',
            'owner_contact' => '080-9999-8888',
            'category'      => '衣類',
            'features'      => '更新された特徴',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('lost_reports', [
            'id'         => $report->id,
            'owner_name' => '更新された氏名',
        ]);
    }

    // ── Web — update status ───────────────────────────────────────────────────

    public function test_patch_lost_reports_status_returns_json_success_true(): void
    {
        $report = LostReport::factory()->searching()->create();

        $response = $this->patchJson("/lost-reports/{$report->id}/status", [
            'status' => '解決済',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // ── API — index ───────────────────────────────────────────────────────────

    public function test_api_get_lost_reports_returns_json(): void
    {
        LostReport::factory()->count(3)->create();

        $response = $this->getJson('/api/lost-reports');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    // ── API — store ───────────────────────────────────────────────────────────

    public function test_api_post_lost_reports_returns_201_with_report_and_matches_array(): void
    {
        // Seed a matching found item so match logic has something to work with
        FoundItem::factory()->storing()->create([
            'category' => '電子機器',
            'features' => 'スマートフォン 黒色',
        ]);

        $response = $this->postJson('/api/lost-reports', [
            'owner_name'    => '鈴木花子',
            'owner_contact' => '080-5678-1234',
            'category'      => '電子機器',
            'features'      => 'スマートフォン 黒色',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'report'  => ['id', 'owner_name', 'category'],
            'matches',
        ]);

        $matches = $response->json('matches');
        $this->assertIsArray($matches);
    }

    // ── API — show ────────────────────────────────────────────────────────────

    public function test_api_get_lost_report_by_id_returns_200(): void
    {
        $report = LostReport::factory()->create();

        $response = $this->getJson("/api/lost-reports/{$report->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $report->id]);
    }

    // ── API — update status ───────────────────────────────────────────────────

    public function test_api_patch_lost_report_status_returns_updated_report(): void
    {
        $report = LostReport::factory()->searching()->create();

        $response = $this->patchJson("/api/lost-reports/{$report->id}/status", [
            'status' => 'キャンセル',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'キャンセル']);
    }

    public function test_api_patch_lost_report_status_with_invalid_status_returns_422(): void
    {
        $report = LostReport::factory()->create();

        $response = $this->patchJson("/api/lost-reports/{$report->id}/status", [
            'status' => '無効ステータス',
        ]);

        $response->assertStatus(422);
    }
}
