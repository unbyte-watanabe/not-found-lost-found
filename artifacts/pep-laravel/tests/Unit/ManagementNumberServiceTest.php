<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FoundItem;
use App\Services\ManagementNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private ManagementNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ManagementNumberService::class);
    }

    public function test_generate_returns_string_matching_pattern(): void
    {
        $result = $this->service->generate();

        $this->assertMatchesRegularExpression('/^\d{8}-\d{4}$/', $result);
    }

    public function test_generate_uses_todays_date_as_prefix(): void
    {
        $result = $this->service->generate();

        $todayPrefix = (new \DateTimeImmutable('today'))->format('Ymd');

        $this->assertStringStartsWith($todayPrefix . '-', $result);
    }

    public function test_generateForDate_uses_provided_date_as_prefix(): void
    {
        $date   = new \DateTimeImmutable('2024-06-15');
        $result = $this->service->generateForDate($date);

        $this->assertStringStartsWith('20240615-', $result);
        $this->assertMatchesRegularExpression('/^20240615-\d{4}$/', $result);
    }

    public function test_generateForDate_first_item_on_date_has_sequence_0001(): void
    {
        $date   = new \DateTimeImmutable('2024-03-01');
        $result = $this->service->generateForDate($date);

        $this->assertSame('20240301-0001', $result);
    }

    public function test_sequential_calls_produce_different_sequence_numbers(): void
    {
        $date = new \DateTimeImmutable('2024-03-01');

        // First call → 0001 (no records exist for this date yet)
        $first = $this->service->generateForDate($date);
        $this->assertSame('20240301-0001', $first);

        // Create a DB record for that date so the second call sees 1 existing record
        FoundItem::factory()->create([
            'management_no'  => $first,
            'found_datetime' => '2024-03-01 10:00:00',
        ]);

        // Second call → 0002
        $second = $this->service->generateForDate($date);
        $this->assertSame('20240301-0002', $second);

        $this->assertNotSame($first, $second);
    }

    public function test_sequence_resets_for_different_dates(): void
    {
        // Create a record for 2024-03-01
        FoundItem::factory()->create([
            'management_no'  => '20240301-0001',
            'found_datetime' => '2024-03-01 09:00:00',
        ]);

        // A different date should start at 0001
        $date   = new \DateTimeImmutable('2024-03-02');
        $result = $this->service->generateForDate($date);

        $this->assertSame('20240302-0001', $result);
    }

    public function test_sequence_is_zero_padded_to_four_digits(): void
    {
        // Create 9 records for today so the next one is 0010
        $todayStr    = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $todayPrefix = (new \DateTimeImmutable('today'))->format('Ymd');

        for ($i = 1; $i <= 9; $i++) {
            FoundItem::factory()->create([
                'management_no'  => $todayPrefix . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'found_datetime' => $todayStr . ' 08:00:00',
            ]);
        }

        $result = $this->service->generate();
        $parts  = explode('-', $result);

        // The sequence part must be exactly 4 digits
        $this->assertSame(4, strlen($parts[1]));
        $this->assertSame('0010', $parts[1]);
    }
}
