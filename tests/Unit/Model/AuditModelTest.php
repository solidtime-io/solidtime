<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\Audit;
use App\Models\Concerns\AuditableWithoutOwner;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Audit::class)]
class AuditModelTest extends ModelTestAbstract
{
    public function test_it_belongs_to_an_owner_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $audit = Audit::factory()->create([
            'owner_organization_id' => $organization->getKey(),
        ]);

        // Act
        $audit->refresh();
        $ownerOrganizationRel = $audit->ownerOrganization;

        // Assert
        $this->assertNotNull($ownerOrganizationRel);
        $this->assertTrue($ownerOrganizationRel->is($organization));
    }

    public function test_it_belongs_to_an_owner_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $audit = Audit::factory()->create([
            'owner_user_id' => $user->getKey(),
        ]);

        // Act
        $audit->refresh();
        $ownerUserRel = $audit->ownerUser;

        // Assert
        $this->assertNotNull($ownerUserRel);
        $this->assertTrue($ownerUserRel->is($user));
    }

    public function test_audits_of_models_with_organization_have_the_organization_as_owner(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $member = Member::factory()->forOrganization($organization)->create();

        // Act
        $timeEntry = TimeEntry::factory()->forOrganization($organization)->forMember($member)->create();

        // Assert
        $audit = Audit::query()->where('auditable_id', $timeEntry->getKey())->sole();
        $this->assertSame($organization->getKey(), $audit->owner_organization_id);
        $this->assertNull($audit->owner_user_id);
    }

    public function test_audits_of_project_members_have_the_organization_of_the_project_as_owner(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        $member = Member::factory()->forOrganization($organization)->create();

        // Act
        $projectMember = ProjectMember::factory()->forProject($project)->forMember($member)->create();

        // Assert
        $audit = Audit::query()->where('auditable_id', $projectMember->getKey())->sole();
        $this->assertSame($organization->getKey(), $audit->owner_organization_id);
        $this->assertNull($audit->owner_user_id);
    }

    public function test_audits_of_an_organization_have_the_organization_itself_as_owner(): void
    {
        // Act
        $organization = Organization::factory()->create();

        // Assert
        $audit = Audit::query()->where('auditable_id', $organization->getKey())->sole();
        $this->assertSame($organization->getKey(), $audit->owner_organization_id);
        $this->assertNull($audit->owner_user_id);
    }

    public function test_audits_of_a_user_have_the_user_itself_as_owner(): void
    {
        // Act
        $user = User::factory()->create();

        // Assert
        $audit = Audit::query()->where('auditable_id', $user->getKey())->sole();
        $this->assertSame($user->getKey(), $audit->owner_user_id);
        $this->assertNull($audit->owner_organization_id);
    }

    public function test_deleting_an_owner_deletes_its_audits_and_does_not_create_a_deletion_audit(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherUser = User::factory()->create();

        // Act
        $user->delete();
        $organization->delete();

        // Assert
        $this->assertSame(0, Audit::query()->where('auditable_id', $user->getKey())->count());
        $this->assertSame(0, Audit::query()->where('auditable_id', $organization->getKey())->count());
        $this->assertSame(1, Audit::query()->where('auditable_id', $otherUser->getKey())->count());
    }

    public function test_auditable_types_without_owner_are_determined_by_the_marker_interface(): void
    {
        // Arrange
        $originalMorphMap = Relation::morphMap();
        $modelWithoutOwner = new class extends Model implements AuditableWithoutOwner {};
        Relation::morphMap(['model-without-owner' => $modelWithoutOwner::class]);

        try {
            // Act
            $typesWithoutOwner = Audit::getAuditableTypesWithoutOwner();
            $isWithoutOwner = Audit::isAuditableTypeWithoutOwner('model-without-owner');
            $isTimeEntryWithoutOwner = Audit::isAuditableTypeWithoutOwner((new TimeEntry)->getMorphClass());

            // Assert
            $this->assertContains('model-without-owner', $typesWithoutOwner);
            $this->assertNotContains((new TimeEntry)->getMorphClass(), $typesWithoutOwner);
            $this->assertTrue($isWithoutOwner);
            $this->assertFalse($isTimeEntryWithoutOwner);
        } finally {
            Relation::morphMap($originalMorphMap, false);
        }
    }

    public function test_scope_where_missing_owner_only_returns_audits_without_owner_whose_type_should_have_one(): void
    {
        // Arrange
        $originalMorphMap = Relation::morphMap();
        $modelWithoutOwner = new class extends Model implements AuditableWithoutOwner {};
        Relation::morphMap(['model-without-owner' => $modelWithoutOwner::class]);
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        Audit::query()->delete();
        $missingOwnerAudit = Audit::factory()->create(['auditable_type' => (new TimeEntry)->getMorphClass()]);
        Audit::factory()->create(['auditable_type' => (new TimeEntry)->getMorphClass(), 'owner_organization_id' => $organization->getKey()]);
        Audit::factory()->create(['owner_user_id' => $user->getKey()]);
        Audit::factory()->create(['auditable_type' => 'model-without-owner']);

        try {
            // Act
            $auditIds = Audit::query()->whereMissingOwner()->pluck('id')->all();

            // Assert
            $this->assertSame([$missingOwnerAudit->getKey()], $auditIds);
        } finally {
            Relation::morphMap($originalMorphMap, false);
        }
    }
}
