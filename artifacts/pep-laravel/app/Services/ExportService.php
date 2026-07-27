<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FoundItem;
use Illuminate\Support\Collection;

/**
 * Generates export files for administrative and police reporting purposes.
 */
final class ExportService
{
    /**
     * UTF-8 BOM sequence for Excel compatibility.
     */
    private const BOM = "\xEF\xBB\xBF";

    /**
     * CSV column headers for police submission export.
     *
     * @var list<string>
     */
    private const POLICE_CSV_HEADERS = [
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

    /**
     * Generate a UTF-8 CSV string for police submission from a collection of FoundItems.
     *
     * The output includes a UTF-8 BOM so that Microsoft Excel opens it correctly
     * without encoding issues.
     *
     * @param Collection<int, FoundItem> $items The found items to export.
     * @return string The complete CSV content including BOM and headers.
     *
     * @throws \RuntimeException If the in-memory stream cannot be opened.
     */
    public function generatePoliceCsv(Collection $items): string
    {
        $stream = fopen('php://temp', 'r+b');

        if ($stream === false) {
            throw new \RuntimeException('CSVの一時ストリームを開けませんでした。');
        }

        try {
            // Write BOM for Excel UTF-8 compatibility
            fwrite($stream, self::BOM);

            // Write header row
            fputcsv($stream, self::POLICE_CSV_HEADERS);

            // Write data rows
            foreach ($items as $item) {
                fputcsv($stream, $this->buildRow($item));
            }

            rewind($stream);
            $csv = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }

        return $csv === false ? '' : $csv;
    }

    /**
     * Build a single CSV data row from a FoundItem.
     *
     * @param FoundItem $item
     * @return list<string>
     */
    private function buildRow(FoundItem $item): array
    {
        $category = $item->category instanceof \BackedEnum
            ? $item->category->value
            : (string) $item->category;

        $status = $item->status instanceof \BackedEnum
            ? $item->status->value
            : (string) $item->status;

        return [
            $item->management_no                                                  ?? '',
            $status,
            $category,
            $item->sub_category                                                   ?? '',
            $item->features,
            $item->found_datetime?->format('Y/m/d H:i')                          ?? '',
            $item->found_location                                                 ?? '',
            $item->storage_location                                               ?? '',
            $item->finder_name                                                    ?? '',
            $item->finder_contact                                                 ?? '',
            $item->rights_waived ? '放棄済' : '未放棄',
            $item->created_at?->format('Y/m/d H:i')                              ?? '',
        ];
    }
}
