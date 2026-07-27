<?php

declare(strict_types=1);

namespace App\Enums;

enum FoundItemCategory: string
{
    case WalletBag   = '財布・カバン類';
    case Clothing    = '衣類';
    case Electronics = '電子機器';
    case Umbrella    = '傘';
    case Other       = 'その他';

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
