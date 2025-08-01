<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FailedJobResource\Pages\ListFailedJobs;
use App\Filament\Resources\FailedJobResource\Pages\ViewFailedJobs;
use App\Models\FailedJob;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Novadaemon\FilamentPrettyJson\Form\PrettyJsonField;

/**
 * @source https://gitlab.com/amvisor/filament-failed-jobs
 */
class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static ?string $navigationGroup = 'System';

    public static function getNavigationBadge(): ?string
    {
        return (string) FailedJob::query()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('uuid')->disabled()->columnSpan(4),
                TextInput::make('failed_at')->disabled(),
                TextInput::make('id')->disabled(),
                TextInput::make('connection')->disabled(),
                TextInput::make('queue')->disabled(),

                // make text a little bit smaller because often a complete Stack Trace is shown:
                TextArea::make('exception')->disabled()->columnSpan(4)->extraInputAttributes(['style' => 'font-size: 80%;']),
                PrettyJsonField::make('payload')->disabled()->columnSpan(4),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable(),
                TextColumn::make('failed_at')->sortable()->searchable(false)->toggleable(),
                TextColumn::make('exception')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->wrap()
                    ->limit(200)
                    ->tooltip(fn (FailedJob $record) => "{$record->failed_at} UUID: {$record->uuid}; Connection: {$record->connection}; Queue: {$record->queue};"),
                TextColumn::make('uuid')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('connection')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('queue')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->bulkActions([
                BulkAction::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->label('Retry selected')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        /** @var FailedJob $record */
                        foreach ($records as $record) {
                            Artisan::call("queue:retry {$record->uuid}");
                        }
                        Notification::make()
                            ->title("{$records->count()} jobs have been pushed back onto the queue.")
                            ->success()
                            ->send();
                    }),
                DeleteBulkAction::make(),
            ])
            ->actions([
                DeleteAction::make(),
                ViewAction::make(),
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->label('Retry')
                    ->requiresConfirmation()
                    ->action(function (FailedJob $record): void {
                        Artisan::call("queue:retry {$record->uuid}");
                        Notification::make()
                            ->title("The job with uuid '{$record->uuid}' has been pushed back onto the queue.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedJobs::route('/'),
            'view' => ViewFailedJobs::route('/{record}'),
        ];
    }
}
