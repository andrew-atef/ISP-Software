<?php

namespace App\Enums;

enum TaskType: string
{
    case NewInstall = 'new_install';
    case DropBury = 'drop_bury';
    case ServiceCall = 'service_call';
    case ServiceChange = 'service_change';
}
