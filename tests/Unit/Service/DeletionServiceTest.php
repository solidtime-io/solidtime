<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\Role;
use App\Events\BeforeOrganizationDeletion;
use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Report;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Service\DeletionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;
use TiMacDonald\Log\LogEntry;

#[CoversClass(DeletionService::class)]
#[UsesClass(DeletionService::class)]
class DeletionServiceTest extends TestCaseWithDatabase
{
    private DeletionService $deletionService;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([
            BeforeOrganizationDeletion::class,
        ]);
        $this->deletionService = app(DeletionService::class);
    }

    /**
     * Creates an organization with all relations.
     * It is important that every relation has at least two entries, to test for possible lazy loading issues.
     *
     * @return object{
     *     organization: Organization,
     *     clients: Collection<Client>,
     *     projects: Collection<Project>,
     *     projectMembers: Collection<ProjectMember>,
     *     tags: Collection<Tag>,
     *     members: Collection<Member>,
     *     tasks: Collection<Task>,
     *     timeEntries: Collection<TimeEntry>,
     *     owner: User,
     *     reports: Collection<Report>
     * }
     */
    private function createOrganizationWithAllRelations(): object
    {
        $userOwner = User::factory()->create();
        $userEmployee = User::factory()->withProfilePicture()->create();
        $userPlaceholder = User::factory()->placeholder()->create();

        $organization = Organization::factory()->withOwner($userOwner)->create();

        // Create a personal organization for the employee
        $personalOrganizationOfEmployee = Organization::factory()->withOwner($userEmployee)->create();
        $personalOrganizationMember = Member::factory()->forUser($userEmployee)->forOrganization($personalOrganizationOfEmployee)->create();

        // Set the current organizations for the users
        $userOwner->update(['current_team_id' => $organization->id]);
        $userEmployee->update(['current_team_id' => $personalOrganizationOfEmployee->id]);
        $userPlaceholder->update(['current_team_id' => null]);

        $memberOwner = Member::factory()->forUser($userOwner)->forOrganization($organization)->role(Role::Owner)->create();
        $memberEmployee = Member::factory()->forUser($userEmployee)->forOrganization($organization)->role(Role::Employee)->create();
        $memberPlaceholder = Member::factory()->forUser($userPlaceholder)->forOrganization($organization)->role(Role::Placeholder)->create();
        $members = collect([$memberOwner, $memberEmployee, $memberPlaceholder]);

        $clients = Client::factory()->forOrganization($organization)->createMany(2);

        $projectWithClient = Project::factory()->forClient($clients->get(0))->forOrganization($organization)->create();
        $projectWithoutClient = Project::factory()->forOrganization($organization)->create();
        $projects = collect([$projectWithClient, $projectWithoutClient]);

        $projectMemberOwner = ProjectMember::factory()->forMember($memberOwner)->forProject($projectWithClient)->create();
        $projectMemberEmployee = ProjectMember::factory()->forMember($memberEmployee)->forProject($projectWithClient)->create();
        $projectMembers = collect([$projectMemberOwner, $projectMemberEmployee]);

        $tags = Tag::factory()->forOrganization($organization)->createMany(2);

        $task1 = Task::factory()->forProject($projectWithClient)->forOrganization($organization)->create();
        $task2 = Task::factory()->forProject($projectWithoutClient)->forOrganization($organization)->create();
        $tasks = collect([$task1, $task2]);

        $report1 = Report::factory()->forOrganization($organization)->create();
        $report2 = Report::factory()->forOrganization($organization)->create();
        $reports = collect([$report1, $report2]);

        $timeEntries = TimeEntry::factory()->forOrganization($organization)->forMember($memberOwner)->createMany(2);
        $timeEntriesWithTask = TimeEntry::factory()->forTask($task1)->forOrganization($organization)->forMember($memberEmployee)->createMany(2);
        $timeEntriesWithProject = TimeEntry::factory()->forProject($projectWithClient)->forOrganization($organization)->forMember($memberPlaceholder)->createMany(2);
        $timeEntries = $timeEntries->merge($timeEntriesWithTask)->merge($timeEntriesWithProject);

        return (object) [
            'organization' => $organization,
            'clients' => $clients,
            'projects' => $projects,
            'projectMembers' => $projectMembers,
            'tags' => $tags,
            'members' => $members,
            'tasks' => $tasks,
            'timeEntries' => $timeEntries,
            'owner' => $userOwner,
            'reports' => $reports,
        ];
    }

    private function assertOrganizationDeleted(Organization $organization): void
    {
        Event::assertDispatched(function (BeforeOrganizationDeletion $event) use ($organization) {
            return $event->organization->is($organization);
        }, 1);
        $this->assertSame(0, Organization::query()->where('id', $organization->id)->count());
        $this->assertSame(0, Client::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(0, Project::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(0, ProjectMember::query()->whereBelongsToOrganization($organization)->count());
        $this->assertSame(0, Tag::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(0, Member::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(0, Task::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(0, Report::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(0, TimeEntry::query()->whereBelongsTo($organization, 'organization')->count());
    }

    private function assertOrganizationNothingDeleted(Organization $organization, bool $specialCase = false): void
    {
        $this->assertSame(1, Organization::query()->where('id', $organization->id)->count());
        $this->assertSame(2, Client::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(2, Project::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(2, ProjectMember::query()->whereBelongsToOrganization($organization)->count());
        $this->assertSame(2, Tag::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(3, Member::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(2, Task::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame(2, Report::query()->whereBelongsTo($organization, 'organization')->count());
        $this->assertSame($specialCase ? 7 : 6, TimeEntry::query()->whereBelongsTo($organization, 'organization')->count());
    }

    public function test_delete_organization_deletes_all_resources_of_the_organization_but_does_not_delete_other_resources(): void
    {
        // Arrange
        $organization = $this->createOrganizationWithAllRelations();
        $otherOrganization = $this->createOrganizationWithAllRelations();

        // Act
        $this->deletionService->deleteOrganization($organization->organization);

        // Assert
        $this->assertOrganizationDeleted($organization->organization);
        $this->assertOrganizationNothingDeleted($otherOrganization->organization);
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Start deleting organization'
            && $log->context['organization_id'] === $organization->organization->getKey(),
            1
        );
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Finished deleting organization'
            && $log->context['organization_id'] === $organization->organization->getKey(),
            1
        );
    }

    public function test_delete_organization_rolls_back_on_error_if_transaction_is_active(): void
    {
        // Arrange
        $organization = $this->createOrganizationWithAllRelations();
        $otherOrganization = $this->createOrganizationWithAllRelations();
        $brokenTimeEntry = TimeEntry::factory()->forOrganization($otherOrganization->organization)->forProject($organization->projects->get(0))->create();

        // Act
        try {
            $this->deletionService->deleteOrganization($organization->organization);
            $this->fail();
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        // Assert
        Event::assertNotDispatched(function (BeforeOrganizationDeletion $event) use ($otherOrganization): bool {
            return $event->organization->is($otherOrganization->organization);
        });
        Event::assertDispatched(function (BeforeOrganizationDeletion $event) use ($organization): bool {
            return $event->organization->is($organization->organization);
        }, 1);
        $this->assertOrganizationNothingDeleted($organization->organization);
        $this->assertOrganizationNothingDeleted($otherOrganization->organization, true);
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Start deleting organization'
            && $log->context['organization_id'] === $organization->organization->getKey(),
            1
        );
        Log::assertNotLogged(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Finished deleting organization'
            && $log->context['organization_id'] === $organization->organization->getKey()
        );
    }

    public function test_delete_user_fails_if_user_is_owner_of_an_organization_with_multiple_members(): void
    {
        // Arrange
        $organization = $this->createOrganizationWithAllRelations();
        $memberOwner = $organization->owner;

        // Act
        try {
            $this->deletionService->deleteUser($memberOwner);
            $this->fail();
        } catch (CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers $exception) {
            // Assert
            $this->assertTrue(true);
        }
    }

    public function test_delete_user_rolls_back_on_error_if_transaction_is_active(): void
    {
        // Arrange
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $memberOwner = Member::factory()->forUser($user)->forOrganization($organization)->role(Role::Owner)->create();
        $otherOrganization = Organization::factory()->create();

        $brokenTimeEntry = TimeEntry::factory()->forMember($memberOwner)->forOrganization($otherOrganization)->create();

        // Act
        try {
            $this->deletionService->deleteUser($user);
            $this->fail();
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        // Assert
        $this->assertDatabaseHas(User::class, [
            'id' => $user->getKey(),
        ]);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $organization->getKey(),
        ]);
        $this->assertDatabaseHas(Member::class, [
            'id' => $memberOwner->getKey(),
        ]);
        $this->assertDatabaseHas(TimeEntry::class, [
            'id' => $brokenTimeEntry->getKey(),
        ]);
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Start deleting user'
            && $log->context['id'] === $user->getKey(),
            1
        );
        Log::assertNotLogged(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Finished deleting user'
            && $log->context['id'] === $user->getKey()
        );
    }

    public function test_delete_user_deletes_all_resources_of_the_user_but_does_not_delete_other_resources(): void
    {
        // Arrange
        $this->mockPublicStorage();
        $user = User::factory()->withProfilePicture()->withPersonalOrganization()->create();
        $otherUser = User::factory()->withProfilePicture()->withPersonalOrganization()->create();
        Storage::disk(config('filesystems.public'))->assertExists($user->profile_photo_path);
        Storage::disk(config('filesystems.public'))->assertExists($otherUser->profile_photo_path);

        // Act
        $this->deletionService->deleteUser($user);

        // Assert
        $this->assertDatabaseMissing(User::class, [
            'id' => $user->getKey(),
        ]);
        $this->assertDatabaseHas(User::class, [
            'id' => $otherUser->getKey(),
        ]);
        $this->assertDatabaseMissing(Organization::class, [
            'id' => $user->current_team_id,
        ]);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $otherUser->current_team_id,
        ]);
        $this->assertDatabaseHas(Member::class, [
            'user_id' => $otherUser->getKey(),
        ]);
        $this->assertDatabaseMissing(Member::class, [
            'user_id' => $user->getKey(),
        ]);
        Storage::disk(config('filesystems.public'))->assertMissing($user->profile_photo_path);
        Storage::disk(config('filesystems.public'))->assertExists($otherUser->profile_photo_path);
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Start deleting user'
            && $log->context['id'] === $user->getKey(),
            1
        );
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Finished deleting user'
            && $log->context['id'] === $user->getKey(),
            1
        );
    }

    public function test_delete_user_deletes_owned_organizations_that_have_only_one_member_and_makes_makes_the_user_placeholder_in_not_owned_organizations(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $organizationOwned = Organization::factory()->withOwner($user)->create();
        $organizationNotOwned = Organization::factory()->withOwner($otherUser)->create();
        $memberOwned = Member::factory()->forUser($user)->forOrganization($organizationOwned)->role(Role::Owner)->create();
        $memberNotOwned = Member::factory()->forUser($user)->forOrganization($organizationNotOwned)->role(Role::Employee)->create();
        TimeEntry::factory()->forOrganization($organizationOwned)->forMember($memberOwned)->createMany(2);
        TimeEntry::factory()->forOrganization($organizationNotOwned)->forMember($memberNotOwned)->createMany(2);
        $this->assertDatabaseCount(User::class, 2);

        // Act
        $this->deletionService->deleteUser($user);

        // Assert
        $this->assertDatabaseCount(Organization::class, 1);
        $this->assertDatabaseCount(User::class, 2);
        $this->assertDatabaseMissing(User::class, [
            'id' => $user->getKey(),
        ]);
        $this->assertDatabaseHas(User::class, [
            'id' => $otherUser->getKey(),
            'is_placeholder' => false,
        ]);
        $this->assertDatabaseHas(User::class, [
            'is_placeholder' => true,
        ]);
        $this->assertDatabaseMissing(Organization::class, [
            'id' => $organizationOwned->getKey(),
        ]);
        $this->assertDatabaseHas(Organization::class, [
            'id' => $organizationNotOwned->getKey(),
        ]);
        $this->assertDatabaseMissing(Member::class, [
            'id' => $memberOwned->getKey(),
        ]);
        $this->assertDatabaseHas(Member::class, [
            'id' => $memberNotOwned->getKey(),
            'organization_id' => $organizationNotOwned->getKey(),
            'role' => Role::Placeholder->value,
        ]);
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Start deleting user'
            && $log->context['id'] === $user->getKey(),
            1
        );
        Log::assertLoggedTimes(fn (LogEntry $log) => $log->level === 'debug'
            && $log->message === 'Finished deleting user'
            && $log->context['id'] === $user->getKey(),
            1
        );
    }
}
