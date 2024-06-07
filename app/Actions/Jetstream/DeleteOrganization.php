<?php

declare(strict_types=1);

namespace App\Actions\Jetstream;

use App\Models\Organization;
use App\Service\DeletionService;
use Laravel\Jetstream\Contracts\DeletesTeams;

class DeleteOrganization implements DeletesTeams
{
    /**
     * Delete the given team.
     */
    public function delete(Organization $organization): void
    {
        app(DeletionService::class)->deleteOrganization($organization);
    }
}
