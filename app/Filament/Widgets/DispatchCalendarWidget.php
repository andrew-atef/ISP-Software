<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Collection;

class DispatchCalendarWidget extends CalendarWidget
{
    protected string $view = 'filament.widgets.dispatch-calendar-widget';

    protected bool $eventClickEnabled = true;

    protected CalendarViewType $calendarView = CalendarViewType::TimeGridWeek;

    public ?int $technicianId = null;

    protected HtmlString|string|bool|null $heading = 'Dispatch Calendar';

    public function getHeaderActions(): array
    {
        return [
            $this->viewAction()->label('View Task')->hidden(),
        ];
    }

    public function updatedTechnicianId(): void
    {
        // Livewire lifecycle hook to refresh calendar events when filter changes
        $this->refreshRecords();
    }

    protected function getEvents(FetchInfo $info): Collection
    {
        $rangeStart = $info->start->startOfDay();
        $rangeEnd = $info->end->endOfDay();

        $query = Task::query()
            ->with(['customer', 'assignedTech'])
            ->whereBetween('scheduled_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);

        if ($this->technicianId) {
            $query->where('assigned_tech_id', $this->technicianId);
        }

        return $query->get();
    }

    public function getTechnicianOptions(): array
    {
        return User::query()
            ->where('role', UserRole::Tech)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
