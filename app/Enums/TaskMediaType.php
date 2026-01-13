<?php

namespace App\Enums;

enum TaskMediaType: string
{
    case Work = 'work';
    case Bury = 'bury';
    case Bore = 'bore';
    case General = 'general';
}
