<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ImportSource;
use App\Enums\ImportStatus;
use App\Filament\Resources\ImportResource\Pages\ListImports;
use App\Filament\Resources\ImportResource\Pages\ViewImport;
use App\Models\Import;
use App\Service\Import\Importers\ImporterProvider;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Novadaemon\FilamentPrettyJson\Form\PrettyJsonField;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Timetracking';

    protected static ?int $navigationSort = 9;

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
                            ->url(fn (?Import $record): ?string => $record !== null
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
                            ->hidden(fn (?Import $record): bool => $record?->user_id === null)
                            ->url(fn (?Import $record): ?string => $record?->user_id !== null
                                ? UserResource::getUrl('view', ['record' => $record->user_id])
                                : null)
                    )
                    ->disabled(),
                TextInput::make('type')
                    ->label('Type')
                    ->disabled(),
                Select::make('source')
                    ->label('Source')
                    ->options(ImportSource::class)
                    ->disabled(),
                Select::make('status')
                    ->label('Status')
                    ->options(ImportStatus::class)
                    ->disabled(),
                TextInput::make('timezone')
                    ->label('Timezone')
                    ->disabled(),
                TextInput::make('file_path')
                    ->label('File path')
                    ->disabled(),
                Toggle::make('ignore_limits')
                    ->label('Ignore limits (admin import)')
                    ->disabled(),
                Textarea::make('error_message')
                    ->label('Error message')
                    ->columnSpanFull()
                    ->disabled(),
                Textarea::make('error_details')
                    ->label('Error details')
                    ->helperText('Exception chain and stack trace, only visible in the admin panel')
                    // The attribute is hidden for serialization, so it is not filled into the form automatically
                    ->formatStateUsing(fn (Import $record): ?string => $record->error_details)
                    ->rows(15)
                    ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 80%;'])
                    ->columnSpanFull()
                    ->disabled(),
                PrettyJsonField::make('report')
                    ->formatStateUsing(fn (Import $record): ?string => $record->getRawOriginal('report'))
                    ->columnSpanFull()
                    ->disabled(),
                PrettyJsonField::make('options')
                    ->formatStateUsing(fn (Import $record): ?string => $record->getRawOriginal('options'))
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
                    ->url(fn (Import $record): string => OrganizationResource::getUrl('view', ['record' => $record->organization_id]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->url(fn (Import $record): ?string => $record->user_id !== null
                        ? UserResource::getUrl('view', ['record' => $record->user_id])
                        : null)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->sortable(),
                TextColumn::make('source')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (Import $record): string => match ($record->status) {
                        ImportStatus::Pending => 'gray',
                        ImportStatus::Running => 'info',
                        ImportStatus::Completed => 'success',
                        ImportStatus::Failed => 'danger',
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
                    ->state(function (Import $record): ?string {
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
                    ->options(ImportStatus::class),
                SelectFilter::make('type')
                    ->options(function (): array {
                        $options = [];
                        foreach (app(ImporterProvider::class)->getImporterKeys() as $key) {
                            $options[$key] = $key;
                        }

                        return $options;
                    }),
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
                    ->hidden(fn (Import $record): bool => $record->file_path === null)
                    ->action(fn (Import $record): StreamedResponse => Storage::disk(config('filesystems.private'))
                        ->download((string) $record->file_path, 'import-'.$record->getKey())),
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
            'index' => ListImports::route('/'),
            'view' => ViewImport::route('/{record}'),
        ];
    }
}
