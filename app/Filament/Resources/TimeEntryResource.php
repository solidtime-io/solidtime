<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TimeEntryResource\Pages;
use App\Models\Member;
use App\Models\TimeEntry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Timetracking';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('id')
                    ->label('ID')
                    ->readOnly()
                    ->disabled(),
                TextInput::make('description')
                    ->label('Description')
                    ->required()
                    ->maxLength(255),
                Toggle::make('billable')
                    ->label('Is Billable?')
                    ->required(),
                DateTimePicker::make('start')
                    ->label('Start')
                    ->required(),
                DateTimePicker::make('end')
                    ->label('End')
                    ->nullable()
                    ->rules([
                        'after_or_equal:start',
                    ]),
                Select::make('member_id')
                    ->relationship(
                        name: 'member',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn (Builder $query) => $query->with(['user', 'organization'])
                    )
                    ->getOptionLabelFromRecordUsing(fn (Member $record): string => $record->user->email.' ('.$record->organization->name.')')
                    ->searchable()
                    ->required(),
                Select::make('project_id')
                    ->relationship(name: 'project', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->nullable(),
                Select::make('task_id')
                    ->relationship(name: 'task', titleAttribute: 'name')
                    ->searchable(['name'])
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->searchable()
                    ->label('Description'),
                TextColumn::make('user.email')
                    ->label('User'),
                TextColumn::make('project.name')
                    ->label('Project'),
                TextColumn::make('task.name')
                    ->label('Task'),
                TextColumn::make('time')
                    ->getStateUsing(function (TimeEntry $record): string {
                        return ($record->getDuration()?->cascade()?->forHumans() ?? '-').' '.
                            ' ('.$record->start->toDateTimeString('minute').' - '.
                            ($record->end?->toDateTimeString('minute') ?? '...').')';
                    })
                    ->label('Time'),
                Tables\Columns\TextColumn::make('organization.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable(),
                SelectFilter::make('organization_id')
                    ->label('Organization ID')
                    ->relationship('organization', 'id')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
