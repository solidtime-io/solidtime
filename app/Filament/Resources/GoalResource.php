<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\GoalType;
use App\Enums\Weekday;
use App\Filament\Resources\GoalResource\Pages;
use App\Models\Goal;
use App\Service\Dto\GoalFiltersDto;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Novadaemon\FilamentPrettyJson\Form\PrettyJsonField;

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Timetracking';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options(collect(GoalType::cases())->mapWithKeys(fn (GoalType $type): array => [$type->value => $type->name])->all())
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('comparison')
                    ->label('Comparison')
                    ->options(collect(GoalComparison::cases())->mapWithKeys(fn (GoalComparison $comparison): array => [$comparison->value => $comparison->name])->all())
                    ->required(),
                Forms\Components\TextInput::make('target_seconds')
                    ->label('Target (seconds)')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Forms\Components\Select::make('period')
                    ->label('Period')
                    ->options(collect(GoalPeriod::cases())->mapWithKeys(fn (GoalPeriod $period): array => [$period->value => $period->name])->all())
                    ->required(),
                Forms\Components\Select::make('timezone')
                    ->label('Timezone')
                    ->options(fn (): array => collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])->all())
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('week_start')
                    ->label('Week start')
                    ->options(collect(Weekday::cases())->mapWithKeys(fn (Weekday $weekday): array => [$weekday->value => $weekday->name])->all())
                    ->required(),
                DateTimePicker::make('archived_at')
                    ->label('Archived At'),
                Forms\Components\Select::make('organization_id')
                    ->label('Organization')
                    ->relationship(name: 'organization', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('member_id')
                    ->label('Member (empty = every member)')
                    ->relationship(name: 'member', titleAttribute: 'id')
                    ->disabled(),
                PrettyJsonField::make('filters')
                    ->formatStateUsing(function (GoalFiltersDto $state, Goal $record): string {
                        return $record->getRawOriginal('filters');
                    })
                    ->disabled(),
                DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->hiddenOn(['create'])
                    ->disabled(),
                DateTimePicker::make('updated_at')
                    ->label('Updated At')
                    ->hiddenOn(['create'])
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('comparison')
                    ->sortable(),
                TextColumn::make('target_seconds')
                    ->label('Target (seconds)')
                    ->sortable(),
                TextColumn::make('period')
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable(),
                TextColumn::make('member.user.email')
                    ->label('Member')
                    ->placeholder('Every member')
                    ->searchable(),
                TextColumn::make('archived_at')
                    ->dateTime()
                    ->placeholder('Not archived')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('organization.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(GoalType::cases())->mapWithKeys(fn (GoalType $type): array => [$type->value => $type->name])->all()),
                SelectFilter::make('organization')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable(),
                SelectFilter::make('organization_id')
                    ->label('Organization ID')
                    ->relationship('organization', 'id')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoals::route('/'),
            'edit' => Pages\EditGoal::route('/{record}/edit'),
            'view' => Pages\ViewGoal::route('/{record}'),
        ];
    }
}
