@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Facades\FilamentColor;
    use Filament\Support\View\Components\ButtonComponent;
    use Guava\Calendar\Enums\Context;

    $headerActions = $this->getCachedHeaderActionsComponent();
    $footerActions = $this->getCachedFooterActionsComponent();
@endphp

<x-filament-widgets::widget>
    <x-filament::section :footer="$footerActions">
        @if ($heading = $this->getHeading())
            <x-slot name="heading">
                {{ $heading }}
            </x-slot>
        @endif

        <x-slot name="afterHeader">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                @if ($headerActions)
                    {{ $headerActions }}
                @endif

                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="technicianId"
                    wire:loading.attr="disabled"
                    class="sm:max-w-xs"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="technicianId"
                        placeholder="Select a Technician to view schedule"
                    >
                        <option value="">Select a Technician to view schedule</option>
                        @foreach ($this->getTechnicianOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <span
                    wire:loading
                    wire:target="technicianId"
                    class="text-sm text-gray-500"
                >Loading…</span>
            </div>
        </x-slot>

        <style>
            .ec-event.ec-preview,
            .ec-now-indicator {
                z-index: 30;
            }
        </style>

        <div
            wire:ignore
            wire:loading.class="opacity-50"
            wire:target="technicianId"
            x-load
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('calendar', 'guava/calendar') }}"
            x-data="calendar({
                view: @js($this->getCalendarView()),
                locale: @js($this->getLocale()),
                firstDay: @js($this->getFirstDay()),
                dayMaxEvents: @js($this->getDayMaxEvents()),
                eventContent: @js($this->getEventContentJs()),
                eventClickEnabled: @js($this->isEventClickEnabled()),
                eventDragEnabled: @js($this->isEventDragEnabled()),
                eventResizeEnabled: @js($this->isEventResizeEnabled()),
                noEventsClickEnabled: @js($this->isNoEventsClickEnabled()),
                dateClickEnabled: @js($this->isDateClickEnabled()),
                dateSelectEnabled: @js($this->isDateSelectEnabled()),
                datesSetEnabled: @js($this->isDatesSetEnabled()),
                viewDidMountEnabled: @js($this->isViewDidMountEnabled()),
                eventAllUpdatedEnabled: @js($this->isEventAllUpdatedEnabled()),
                hasDateClickContextMenu: @js($this->hasContextMenu(Context::DateClick)),
                hasDateSelectContextMenu: @js($this->hasContextMenu(Context::DateSelect)),
                hasEventClickContextMenu: @js($this->hasContextMenu(Context::EventClick)),
                hasNoEventsClickContextMenu: @js($this->hasContextMenu(Context::NoEventsClick)),
                resources: @js($this->getResourcesJs()),
                resourceLabelContent: @js($this->getResourceLabelContentJs()),
                theme: @js($this->getTheme()),
                options: @js($this->getOptions()),
                eventAssetUrl: @js(FilamentAsset::getAlpineComponentSrc('calendar-event', 'guava/calendar')),
            })"
            @class(FilamentColor::getComponentClasses(ButtonComponent::class, 'primary'))
        >
            <div data-calendar></div>
            @if ($this->hasContextMenu())
                <x-guava-calendar::context-menu />
            @endif
        </div>
    </x-filament::section>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
