<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ExportStatus;
use App\Enums\ExportType;
use App\Filament\Resources\ExportResource\Pages\ListExports;
use App\Filament\Resources\ExportResource\Pages\ViewExport;
use App\Models\Export;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Timetracking';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('id')
                    ->label('ID')
                    ->disabled(),
                Select::make('organization_id')
                    ->label('Organization')
                    ->relationship(name: 'organization', titleAttribute: 'name')
                    ->hintAction(
                        Action::make('open-organization')
                            ->label('Open organization')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->url(fn (?Export $record): ?string => $record !== null
                                ? OrganizationResource::getUrl('view', ['record' => $record->organization_id])
                                : null)
                    )
                    ->disabled(),
                Select::make('user_id')
                    ->label('User')
                    ->relationship(name: 'user', titleAttribute: 'email')
                    ->hintAction(
                        Action::make('open-user')
                            ->label('Open user')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->hidden(fn (?Export $record): bool => $record?->user_id === null)
                            ->url(fn (?Export $record): ?string => $record?->user_id !== null
                                ? UserResource::getUrl('view', ['record' => $record->user_id])
                                : null)
                    )
                    ->disabled(),
                Select::make('type')
                    ->label('Type')
                    ->options(ExportType::class)
                    ->disabled(),
                Select::make('status')
                    ->label('Status')
                    ->options(ExportStatus::class)
                    ->disabled(),
                TextInput::make('file_path')
                    ->label('File path')
                    ->disabled(),
                Textarea::make('error_message')
                    ->label('Error message')
                    ->columnSpanFull()
                    ->disabled(),
                Textarea::make('error_details')
                    ->label('Error details')
                    ->helperText('Exception chain and stack trace, only visible in the admin panel')
                    // The attribute is hidden for serialization, so it is not filled into the form automatically
                    ->formatStateUsing(fn (Export $record): ?string => $record->error_details)
                    ->rows(15)
                    ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 80%;'])
                    ->columnSpanFull()
                    ->disabled(),
                DateTimePicker::make('created_at')
                    ->label('Created at')
                    ->disabled(),
                DateTimePicker::make('started_at')
                    ->label('Started at')
                    ->disabled(),
                DateTimePicker::make('finished_at')
                    ->label('Finished at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->url(fn (Export $record): string => OrganizationResource::getUrl('view', ['record' => $record->organization_id]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->url(fn (Export $record): ?string => $record->user_id !== null
                        ? UserResource::getUrl('view', ['record' => $record->user_id])
                        : null)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (Export $record): string => match ($record->status) {
                        ExportStatus::Pending => 'gray',
                        ExportStatus::Running => 'info',
                        ExportStatus::Completed => 'success',
                        ExportStatus::Failed => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('error_message')
                    ->limit(80)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function (Export $record): ?string {
                        if ($record->started_at === null || $record->finished_at === null) {
                            return null;
                        }

                        return $record->started_at->diffForHumans($record->finished_at, [
                            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                            'parts' => 2,
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ExportStatus::class),
                SelectFilter::make('type')
                    ->options(ExportType::class),
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
                Action::make('download-file')
                    ->label('File')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->hidden(fn (Export $record): bool => $record->file_path === null)
                    ->action(fn (Export $record): StreamedResponse => Storage::disk(config('filesystems.private'))
                        ->download((string) $record->file_path, $record->getDownloadFilename('UTC'))),
                ViewAction::make(),
            ])
            ->toolbarActions([
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExports::route('/'),
            'view' => ViewExport::route('/{record}'),
        ];
    }
}
