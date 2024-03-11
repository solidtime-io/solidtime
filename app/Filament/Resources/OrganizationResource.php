<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use App\Service\Import\Importers\ImporterProvider;
use App\Service\Import\Importers\ImportException;
use App\Service\Import\Importers\ReportDto;
use App\Service\Import\ImportService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Import')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->action(function (Organization $record, array $data) {
                        // TODO: different disk!
                        try {
                            /** @var ReportDto $report */
                            $report = app(ImportService::class)->import($record, $data['type'], Storage::disk('public')->get($data['file']));
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
                            // TODO: disk!
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
                    ]),
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
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
