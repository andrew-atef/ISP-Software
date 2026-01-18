<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InstallationType: string implements HasLabel, HasColor
{
    case Aerial = 'aerial';
    case Underground = 'underground';
    case Combination = 'combination';
    case MDU = 'mdu';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Aerial => 'Aerial',
            self::Underground => 'Underground',
            self::Combination => 'Combination (Aerial & Underground)',
            self::MDU => 'MDU (Multi-Dwelling Unit)',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Aerial => 'sky',
            self::Underground => 'amber',
            self::Combination => 'purple',
            self::MDU => 'violet',
        };
    }
}
