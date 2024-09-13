<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Admin;

use App\Console\Commands\Admin\OrganizationDeleteCommand;
use App\Models\Organization;
use App\Service\DeletionService;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[CoversClass(OrganizationDeleteCommand::class)]
#[UsesClass(OrganizationDeleteCommand::class)]
class OrganizationDeleteCommandTest extends TestCaseWithDatabase
{
    public function test_it_calls_the_deletion_service_with_the_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $this->mock(DeletionService::class, function (MockInterface $mock) use ($organization): void {
            $mock->shouldReceive('deleteOrganization')
                ->withArgs(fn (Organization $organizationArg) => $organizationArg->is($organization))
                ->once();
        });

        // Act
        $this->artisan('admin:organization:delete', ['organization' => $organization->getKey()])
            ->expectsOutput("Deleting organization with ID {$organization->getKey()}")
            ->expectsOutput("Organization with ID {$organization->getKey()} has been deleted.")
            ->assertExitCode(0);
    }

    public function test_it_fails_if_organization_does_not_exist(): void
    {
        // Arrange
        $organizationId = Str::uuid()->toString();

        // Act
        $this->artisan('admin:organization:delete', ['organization' => $organizationId])
            ->expectsOutput('Organization with ID '.$organizationId.' not found.')
            ->assertExitCode(1);
    }

    public function test_it_fails_if_organization_id_is_not_a_valid_uuid(): void
    {
        // Arrange
        $organizationId = 'invalid-uuid';

        // Act
        $this->artisan('admin:organization:delete', ['organization' => $organizationId])
            ->expectsOutput('Organization ID must be a valid UUID.')
            ->assertExitCode(1);
    }
}
