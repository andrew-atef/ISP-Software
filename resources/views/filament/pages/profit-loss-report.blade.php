<x-filament-panels::page>
    <div class="space-y-10">
        <div class="sticky top-0 z-10 -mx-6 px-6 py-4 backdrop-blur supports-backdrop-blur:bg-white/70 dark:supports-backdrop-blur:bg-gray-900/70">
            <div class="rounded-2xl border border-white/50 bg-white/80 shadow-lg ring-1 ring-gray-900/5 backdrop-blur dark:border-white/10 dark:bg-gray-900/80 dark:ring-white/10">
                <form wire:submit="filter" class="flex flex-wrap items-end gap-4 p-6">
                    {{ $this->form }}

                    <div class="flex">
                        <x-filament::button type="submit" color="primary">
                            Filter
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        <div class="px-2">
            @livewire(\App\Filament\Widgets\ProfitLossStats::class, [
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ], key('profit-loss-stats-' . $this->startDate . $this->endDate))
        </div>

        <div class="grid grid-cols-1 gap-4 px-2">
            @livewire(\App\Filament\Widgets\ProfitLossTrendChart::class, [
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ], key('profit-loss-chart-' . $this->startDate . $this->endDate))
        </div>

        <div class="grid grid-cols-1 gap-8 px-2 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-primary-500" />
                        <span>Invoices (Revenue)</span>
                    </div>
                </x-slot>
                <x-slot name="description">Included invoices where status is not Draft.</x-slot>

                <div class="space-y-3">
                    @forelse ($this->invoices as $invoice)
                        <div class="rounded-xl border border-gray-200/60 bg-white px-5 py-4 shadow-sm ring-1 ring-gray-900/5 transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800/60 dark:bg-gray-900 dark:ring-white/5">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $invoice->invoice_number }}
                                    </div>
                                    <x-filament::badge
                                        color="{{ match($invoice->status) {
                                            'Paid' => 'success',
                                            'Sent' => 'warning',
                                            default => 'gray',
                                        } }}"
                                    >
                                        {{ $invoice->status }}
                                    </x-filament::badge>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                    <div class="text-gray-500">
                                        {{ optional($invoice->period_start)?->format('M d, Y') }}
                                        &middot;
                                        {{ optional($invoice->period_end)?->format('M d, Y') }}
                                    </div>
                                    <div class="font-mono text-base font-semibold text-emerald-600 dark:text-emerald-400">
                                        ${{ number_format((float) $invoice->total_amount, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-inbox" class="h-8 w-8" />
                            <div class="text-sm">No records found</div>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-wallet" class="h-5 w-5 text-primary-500" />
                        <span>Payrolls (Expenses)</span>
                    </div>
                </x-slot>
                <x-slot name="description">Paid payrolls included in this calculation.</x-slot>

                <div class="space-y-3">
                    @forelse ($this->payrolls as $payroll)
                        @php
                            $netPay = (float) $payroll->net_pay;
                            $netColor = $netPay < 0 ? 'text-emerald-600 dark:text-emerald-400' : ($netPay > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500');
                            $statusColor = match($payroll->status?->value) {
                                'paid' => 'success',
                                'draft' => 'gray',
                                default => 'gray',
                            };
                        @endphp

                        <div class="rounded-xl border border-gray-200/60 bg-white px-5 py-4 shadow-sm ring-1 ring-gray-900/5 transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800/60 dark:bg-gray-900 dark:ring-white/5">
                            <div class="flex flex-col gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $payroll->user?->name ?? 'N/A' }}
                                    </div>
                                    <x-filament::badge color="{{ $statusColor }}">
                                        {{ $payroll->status?->getLabel() ?? 'N/A' }}
                                    </x-filament::badge>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                    <div class="text-gray-500">
                                        Week {{ $payroll->week_number }}, {{ $payroll->year }}
                                        &middot;
                                        {{ optional($payroll->created_at)?->format('M d, Y') }}
                                    </div>
                                    <div class="font-mono text-base font-semibold {{ $netColor }}">
                                        ${{ number_format(abs($netPay), 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-gray-200 px-6 py-10 text-center text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-inbox" class="h-8 w-8" />
                            <div class="text-sm">No records found</div>
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
