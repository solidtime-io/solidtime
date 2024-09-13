<?php

declare(strict_types=1);

namespace App\Console\Commands\Admin;

use App\Models\Organization;
use App\Service\DeletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OrganizationDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:organization:delete
                { organization : The ID of the organization to delete }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a organization';

    /**
     * Execute the console command.
     */
    public function handle(DeletionService $deletionService): int
    {
        $organizationId = $this->argument('organization');

        if (! Str::isUuid($organizationId)) {
            $this->error('Organization ID must be a valid UUID.');

            return self::FAILURE;

        }

        /** @var Organization|null $organization */
        $organization = Organization::find($organizationId);
        if ($organization === null) {
            $this->error('Organization with ID '.$organizationId.' not found.');

            return self::FAILURE;
        }

        $this->info('Deleting organization with ID '.$organization->getKey());

        $deletionService->deleteOrganization($organization);

        $this->info('Organization with ID '.$organization->getKey().' has been deleted.');

        return self::SUCCESS;
    }
}
