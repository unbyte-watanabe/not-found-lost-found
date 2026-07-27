<?php

declare(strict_types=1);

namespace App\Enums;

enum LostReportStatus: string
{
    case Searching = '探索中';
    case Resolved  = '解決済';
    case Cancelled = 'キャンセル';

    public function label(): string
    {
        return $this->value;
    }

    public static function fromLabel(string $label): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $label) {
                return $case;
            }
        }

        throw new \ValueError("'{$label}' is not a valid label for " . self::class);
    }
}
