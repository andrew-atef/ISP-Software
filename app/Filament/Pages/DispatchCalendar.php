<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DispatchCalendar extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Dispatch Calendar';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Dispatch Calendar';

    protected string $view = 'filament.pages.dispatch-calendar';
}
