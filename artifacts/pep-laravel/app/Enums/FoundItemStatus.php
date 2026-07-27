<?php

declare(strict_types=1);

namespace App\Enums;

enum FoundItemStatus: string
{
    case Storing         = '保管中';
    case Returned        = '返還済';
    case PoliceSubmitted = '警察提出済';
    case Disposed        = '期間満了処分';

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
