<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrganizationResource\Actions;

use App\Exceptions\Api\ApiException;
use App\Models\Organization;
use App\Service\DeletionService;
use Filament\Actions\DeleteAction;
use Throwable;

class DeleteOrganization extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->icon('heroicon-m-trash');
        $this->action(function (): void {
            $result = $this->process(function (Organization $record): bool {
                try {
                    $deletionService = app(DeletionService::class);
                    $deletionService->deleteOrganization($record);

                    return true;
                } catch (ApiException $exception) {
                    $this->failureNotificationTitle($exception->getTranslatedMessage());
                    report($exception);
                } catch (Throwable $exception) {
                    $this->failureNotificationTitle(__('exceptions.unknown_error_in_admin_panel'));
                    report($exception);
                }

                return false;
            });

            if (! $result) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }
}
