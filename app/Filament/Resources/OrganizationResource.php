<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Filament\Resources\OrganizationResource\RelationManagers\UsersRelationManager;
use App\Models\Organization;
use App\Service\Export\ExportService;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use App\Service\Import\ImportService;
use App\Service\TimezoneService;
use Brick\Money\ISOCurrencyProvider;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Users';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('personal_team')
                    ->label('Is personal?')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship(name: 'owner', titleAttribute: 'email')
                    ->searchable(['name', 'email'])
                    ->required(),
                Forms\Components\Select::make('currency')
                    ->label('Currency')
                    ->options(function (): array {
                        $currencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
                        $select = [];
                        foreach ($currencies as $currency) {
                            $select[$currency->getCurrencyCode()] = $currency->getName().' ('.$currency->getCurrencyCode().')';
                        }

                        return $select;
                    })
                    ->searchable(),
                Forms\Components\TextInput::make('billable_rate')
                    ->label('Billable rate (in Cents)')
                    ->nullable()
                    ->rules([
                        'nullable',
                        'integer',
                        'gt:0',
                    ])
                    ->numeric(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')
                    ->label('Updated At')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('personal_team')
                    ->boolean()
                    ->label('Is personal?')
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.email')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency'),
                TextColumn::make('billable_rate')
                    ->money(fn (Organization $resource) => $resource->currency ?? 'EUR', divideBy: 100),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Organization $record) {
                        try {
                            $file = app(ExportService::class)->export($record);
                            Notification::make()
                                ->title('Export successful')
                                ->success()
                                ->persistent()
                                ->send();

                            return response()->streamDownload(function () use ($file): void {
                                echo Storage::disk(config('filesystems.private'))->get($file);
                            }, 'export.zip');
                        } catch (\Exception $exception) {
                            report($exception);
                            Notification::make()
                                ->title('Export failed')
                                ->danger()
                                ->body('Message: '.$exception->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('Import')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->action(function (Organization $record, array $data): void {
                        try {
                            $file = Storage::disk(config('filament.default_filesystem_disk'))->get($data['file']);
                            if ($file === null) {
                                throw new \Exception('File not found');
                            }
                            /** @var string $timezone */
                            $timezone = $data['timezone'];
                            /** @var ReportDto $report */
                            $report = app(ImportService::class)->import(
                                $record,
                                $data['type'],
                                $file,
                                $timezone
                            );
                            Notification::make()
                                ->title('Import successful')
                                ->success()
                                ->body(
                                    'Imported time entries: '.$report->timeEntriesCreated.'<br>'.
                                    'Imported clients: '.$report->clientsCreated.'<br>'.
                                    'Imported projects: '.$report->projectsCreated.'<br>'.
                                    'Imported tasks: '.$report->tasksCreated.'<br>'.
                                    'Imported tags: '.$report->tagsCreated.'<br>'.
                                    'Imported users: '.$report->usersCreated
                                )
                                ->persistent()
                                ->send();
                        } catch (ImportException $exception) {
                            report($exception);
                            Notification::make()
                                ->title('Import failed, changes rolled back')
                                ->danger()
                                ->body('Message: '.$exception->getMessage())
                                ->persistent()
                                ->send();
                        }
                    })
                    ->tooltip(fn (Organization $record): string => 'Import into '.$record->name)
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('File')
                            ->required(),
                        Select::make('type')
                            ->required()
                            ->options(function (): array {
                                $select = [];
                                foreach (app(ImporterProvider::class)->getImporterKeys() as $key) {
                                    $select[$key] = $key;
                                }

                                return $select;
                            }),
                        Forms\Components\Select::make('timezone')
                            ->label('Timezone')
                            ->options(fn (): array => app(TimezoneService::class)->getSelectOptions())
                            ->searchable()
                            ->required(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
            'view' => Pages\ViewOrganization::route('/{record}'),
        ];
    }
}
