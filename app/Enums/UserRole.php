<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Dispatch = 'dispatch';
    case Tech = 'tech';
}
