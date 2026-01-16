<?php

namespace App\Filament\Resources\PayrollResource\RelationManagers;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Task;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayrollItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';
    protected static ?string $title = 'Payroll Items (Tasks)';
    protected static ?string $recordTitleAttribute = 'description';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', TaskStatus::Approved))
            ->columns([
                Tables\Columns\TextColumn::make('task_type')
                    ->label('Task Type')
                    ->badge()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Count::make()->label('Total Tasks')),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tech_price')
                    ->label('Tech Pay')
                    ->money('USD')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Average::make()->money('USD')->label('Avg Rate'),
                        Tables\Columns\Summarizers\Sum::make()->money('USD')->label('Total Pay'),
                    ]),

                Tables\Columns\TextColumn::make('completion_date')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('financial_status')
                    ->label('Billable')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('task_type')
                    ->options(TaskType::class),
            ])
            ->headerActions([
                // No create/attach - tasks are linked via wizard
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - prevent accidental unlinking
            ])
            ->defaultSort('completion_date', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}
