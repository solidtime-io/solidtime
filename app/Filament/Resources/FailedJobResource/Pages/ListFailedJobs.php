<?php

declare(strict_types=1);

namespace App\Filament\Resources\FailedJobResource\Pages;

use App\Filament\Resources\FailedJobResource;
use App\Models\FailedJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListFailedJobs extends ListRecords
{
    protected static string $resource = FailedJobResource::class;

    public function getHeaderActions(): array
    {
        return [
            Action::make('retry_all')
                ->icon('heroicon-o-arrow-path')
                ->label('Retry all')
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('queue:retry all');
                    Notification::make()
                        ->title('All failed jobs have been pushed back onto the queue.')
                        ->success()
                        ->send();
                }),

            Action::make('delete_all')
                ->icon('heroicon-o-trash')
                ->label('Delete all')
                ->requiresConfirmation()
                ->color('danger')
                ->action(function (): void {
                    FailedJob::truncate();
                    Notification::make()
                        ->title('All failed jobs have been removed.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
