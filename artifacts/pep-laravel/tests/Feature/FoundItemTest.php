<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FoundItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundItemTest extends TestCase
{
    use RefreshDatabase;

    // ── Web — index ───────────────────────────────────────────────────────────

    public function test_found_items_index_returns_200(): void
    {
        $response = $this->get('/found-items');

        $response->assertStatus(200);
    }

    // ── Web — create ──────────────────────────────────────────────────────────

    public function test_found_items_create_returns_200(): void
    {
        $response = $this->get('/found-items/create');

        $response->assertStatus(200);
    }

    // ── Web — store ───────────────────────────────────────────────────────────

    public function test_post_found_items_with_valid_data_redirects_to_show_page(): void
    {
        $response = $this->post('/found-items', [
            'category'       => '財布・カバン類',
            'features'       => '黒色の二つ折り財布',
            'found_datetime' => '2024-01-15 10:30:00',
            'found_location' => 'メインエントランス',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('found_items', [
            'category' => '財布・カバン類',
            'features' => '黒色の二つ折り財布',
        ]);

        $item = FoundItem::where('category', '財布・カバン類')->first();
        $response->assertRedirect(route('found-items.show', $item->id));
    }

    public function test_post_found_items_with_missing_required_fields_returns_validation_errors(): void
    {
        $response = $this->post('/found-items', []);

        // Redirected back with errors (web form validation)
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['category', 'features', 'found_datetime']);
    }

    // ── Web — show ────────────────────────────────────────────────────────────

    public function test_found_items_show_returns_200_for_existing_item(): void
    {
        $item = FoundItem::factory()->create();

        $response = $this->get("/found-items/{$item->id}");

        $response->assertStatus(200);
    }

    public function test_found_items_show_returns_404_for_non_existing_item(): void
    {
        $response = $this->get('/found-items/non-existing-uuid-12345');

        $response->assertStatus(404);
    }

    // ── Web — edit ────────────────────────────────────────────────────────────

    public function test_found_items_edit_returns_200(): void
    {
        $item = FoundItem::factory()->create();

        $response = $this->get("/found-items/{$item->id}/edit");

        $response->assertStatus(200);
    }

    // ── Web — update ──────────────────────────────────────────────────────────

    public function test_put_found_items_with_valid_data_redirects(): void
    {
        $item = FoundItem::factory()->create();

        $response = $this->put("/found-items/{$item->id}", [
            'features'       => '更新された特徴',
            'found_datetime' => '2024-01-20 09:00:00',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('found_items', [
            'id'       => $item->id,
            'features' => '更新された特徴',
        ]);
    }

    // ── Web — destroy ─────────────────────────────────────────────────────────

    public function test_delete_found_item_redirects_to_index(): void
    {
        $item = FoundItem::factory()->create();

        $response = $this->delete("/found-items/{$item->id}");

        $response->assertStatus(302);
        $response->assertRedirect(route('found-items.index'));

        $this->assertDatabaseMissing('found_items', ['id' => $item->id]);
    }

    // ── Web — update status ───────────────────────────────────────────────────

    public function test_patch_found_items_status_returns_json_success_true(): void
    {
        $item = FoundItem::factory()->storing()->create();

        $response = $this->patchJson("/found-items/{$item->id}/status", [
            'status' => '返還済',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_patch_found_items_status_with_invalid_status_returns_422(): void
    {
        $item = FoundItem::factory()->create();

        // Use the API endpoint which returns proper JSON validation errors.
        $response = $this->patchJson("/api/found-items/{$item->id}/status", [
            'status' => '無効なステータス',
        ]);

        $this->assertEquals(422, $response->status());
    }

    // ── API — index ───────────────────────────────────────────────────────────

    public function test_api_get_found_items_returns_json_with_data_array(): void
    {
        FoundItem::factory()->count(3)->create();

        $response = $this->getJson('/api/found-items');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_api_get_found_items_filtered_by_status(): void
    {
        FoundItem::factory()->storing()->count(2)->create();
        FoundItem::factory()->returned()->count(1)->create();

        $response = $this->getJson('/api/found-items?status=保管中');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $item) {
            $this->assertSame('保管中', $item['status']);
        }
    }

    public function test_api_get_found_items_filtered_by_keyword(): void
    {
        FoundItem::factory()->create([
            'features'  => '黒い財布、中にカードあり',
            'category'  => '財布・カバン類',
        ]);
        FoundItem::factory()->create([
            'features' => '傘、折り畳み式',
            'category' => '傘',
        ]);

        $response = $this->getJson('/api/found-items?keyword=財布');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $item) {
            $this->assertStringContainsString('財布', $item['features']);
        }
    }

    // ── API — store ───────────────────────────────────────────────────────────

    public function test_api_post_found_items_returns_201_with_management_no(): void
    {
        $response = $this->postJson('/api/found-items', [
            'category'       => '電子機器',
            'features'       => 'iPhoneケース付き',
            'found_datetime' => '2024-01-15 10:30:00',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['management_no']);

        $managementNo = $response->json('management_no');
        $this->assertMatchesRegularExpression('/^\d{8}-\d{4}$/', $managementNo);
    }

    // ── API — show ────────────────────────────────────────────────────────────

    public function test_api_get_found_item_by_id_returns_200(): void
    {
        $item = FoundItem::factory()->create();

        $response = $this->getJson("/api/found-items/{$item->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $item->id]);
    }

    // ── API — update ──────────────────────────────────────────────────────────

    public function test_api_put_found_item_returns_200_with_updated_data(): void
    {
        $item = FoundItem::factory()->create();

        $response = $this->putJson("/api/found-items/{$item->id}", [
            'features' => 'API経由で更新された特徴',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['features' => 'API経由で更新された特徴']);
    }

    // ── API — destroy ─────────────────────────────────────────────────────────

    public function test_api_delete_found_item_returns_json_success_true(): void
    {
        $item = FoundItem::factory()->create();

        $response = $this->deleteJson("/api/found-items/{$item->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('found_items', ['id' => $item->id]);
    }

    // ── API — update status ───────────────────────────────────────────────────

    public function test_api_patch_found_item_status_returns_updated_item(): void
    {
        $item = FoundItem::factory()->storing()->create();

        $response = $this->patchJson("/api/found-items/{$item->id}/status", [
            'status' => '警察提出済',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => '警察提出済']);
    }

    // ── API — export police ───────────────────────────────────────────────────

    public function test_api_get_export_police_returns_200_with_csv_content_type(): void
    {
        FoundItem::factory()->count(2)->create();

        $response = $this->get('/api/found-items/export/police');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
