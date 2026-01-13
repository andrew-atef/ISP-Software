<?php

namespace App\Enums;

enum TaskFinancialStatus: string
{
    case Billable = 'billable';
    case NotBillable = 'not_billable';
}
