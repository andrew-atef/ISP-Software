<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaskType: string implements HasLabel
{
    case NewInstall = 'new_install';
    case DropBury = 'drop_bury';
    case ServiceCall = 'service_call';
    case ServiceChange = 'service_change';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NewInstall => 'New Install',
            self::DropBury => 'Drop Bury',
            self::ServiceCall => 'Service Call',
            self::ServiceChange => 'Service Change',
        };
    }
}
