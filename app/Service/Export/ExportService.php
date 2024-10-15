<?php

declare(strict_types=1);

namespace App\Service\Export;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception as LeagueCsvException;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ZipArchive;

class ExportService
{
    public const string VERSION = '1.0';

    /**
     * @throws ExportException
     */
    public function export(Organization $organization): string
    {
        $exportId = Str::uuid();
        $timeStamp = Carbon::now();
        $temporaryDirectory = TemporaryDirectory::make();
        Log::debug('Start exporting organization', [
            'organization_id' => $organization->getKey(),
            'export_id' => $exportId,
        ]);

        // Organizations
        try {
            $writer = Writer::createFromPath($temporaryDirectory->path('organizations.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'name',
                'billable_rate',
                'currency',
                'created_at',
                'updated_at',
            ]);
            $writer->insertOne([
                $organization->id,
                $organization->name,
                $organization->billable_rate ?? '',
                $organization->currency,
                $organization->created_at?->toIso8601ZuluString() ?? '',
                $organization->updated_at?->toIso8601ZuluString() ?? '',
            ]);

            // Organization invitations
            $writer = Writer::createFromPath($temporaryDirectory->path('organization_invitations.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'email',
                'organization_id',
                'role',
                'created_at',
                'updated_at',
            ]);
            OrganizationInvitation::query()
                ->whereBelongsTo($organization, 'organization')
                ->chunk(1000, function (Collection $organizationInvitations) use (&$writer): void {
                    $organizationInvitations->each(function (OrganizationInvitation $organizationInvitation) use (&$writer): void {
                        $writer->insertOne([
                            $organizationInvitation->id,
                            $organizationInvitation->email,
                            $organizationInvitation->organization_id,
                            $organizationInvitation->role,
                            $organizationInvitation->created_at?->toIso8601ZuluString() ?? '',
                            $organizationInvitation->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Time entries
            $writer = Writer::createFromPath($temporaryDirectory->path('time_entries.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'description',
                'start',
                'end',
                'billable_rate',
                'billable',
                'member_id',
                'user_id',
                'organization_id',
                'client_id',
                'project_id',
                'task_id',
                'tags',
                'is_imported',
                'still_active_email_sent_at',
                'created_at',
                'updated_at',
            ]);
            TimeEntry::query()
                ->whereBelongsTo($organization, 'organization')
                ->chunk(1000, function (Collection $timeEntries) use (&$writer): void {
                    $timeEntries->each(function (TimeEntry $timeEntry) use (&$writer): void {
                        $tags = json_encode($timeEntry->tags);
                        $writer->insertOne([
                            $timeEntry->id,
                            $timeEntry->description,
                            $timeEntry->start->toIso8601ZuluString(),
                            $timeEntry->end?->toIso8601ZuluString() ?? '',
                            $timeEntry->billable_rate ?? '',
                            $timeEntry->billable ? 'true' : 'false',
                            $timeEntry->member_id,
                            $timeEntry->user_id,
                            $timeEntry->organization_id,
                            $timeEntry->client_id ?? '',
                            $timeEntry->project_id ?? '',
                            $timeEntry->task_id ?? '',
                            $tags === false ? '' : $tags,
                            $timeEntry->is_imported ? 'true' : 'false',
                            $timeEntry->still_active_email_sent_at?->toIso8601ZuluString() ?? '',
                            $timeEntry->created_at?->toIso8601ZuluString() ?? '',
                            $timeEntry->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Clients
            $writer = Writer::createFromPath($temporaryDirectory->path('clients.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'name',
                'organization_id',
                'archived_at',
                'created_at',
                'updated_at',
            ]);
            Client::query()
                ->whereBelongsTo($organization, 'organization')
                ->chunk(1000, function (Collection $clients) use (&$writer): void {
                    $clients->each(function (Client $client) use (&$writer): void {
                        $writer->insertOne([
                            $client->id,
                            $client->name,
                            $client->organization_id,
                            $client->archived_at ?? '',
                            $client->created_at?->toIso8601ZuluString() ?? '',
                            $client->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Projects
            $writer = Writer::createFromPath($temporaryDirectory->path('projects.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'name',
                'color',
                'billable_rate',
                'is_public',
                'client_id',
                'organization_id',
                'is_billable',
                'archived_at',
                'created_at',
                'updated_at',
            ]);
            Project::query()
                ->whereBelongsTo($organization, 'organization')
                ->chunk(1000, function (Collection $projects) use (&$writer): void {
                    $projects->each(function (Project $project) use (&$writer): void {
                        $writer->insertOne([
                            $project->id,
                            $project->name,
                            $project->color,
                            $project->billable_rate ?? '',
                            $project->is_public ? 'true' : 'false',
                            $project->client_id ?? '',
                            $project->organization_id,
                            $project->is_billable ? 'true' : 'false',
                            $project->archived_at?->toIso8601ZuluString() ?? '',
                            $project->created_at?->toIso8601ZuluString() ?? '',
                            $project->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Project members
            $writer = Writer::createFromPath($temporaryDirectory->path('project_members.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'billable_rate',
                'project_id',
                'user_id',
                'member_id',
                'created_at',
                'updated_at',
            ]);
            ProjectMember::query()
                ->whereBelongsToOrganization($organization)
                ->chunk(1000, function (Collection $projectMembers) use (&$writer): void {
                    $projectMembers->each(function (ProjectMember $projectMember) use (&$writer): void {
                        $writer->insertOne([
                            $projectMember->id,
                            $projectMember->billable_rate ?? '',
                            $projectMember->project_id,
                            $projectMember->user_id,
                            $projectMember->member_id,
                            $projectMember->created_at?->toIso8601ZuluString() ?? '',
                            $projectMember->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Members
            $writer = Writer::createFromPath($temporaryDirectory->path('members.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'user_id',
                'name',
                'email',
                'organization_id',
                'billable_rate',
                'role',
                'created_at',
                'updated_at',
            ]);
            Member::query()
                ->whereBelongsTo($organization, 'organization')
                ->with([
                    'user',
                ])
                ->chunk(1000, function (Collection $members) use (&$writer): void {
                    $members->each(function (Member $member) use (&$writer): void {
                        $writer->insertOne([
                            $member->id,
                            $member->user_id,
                            $member->user->name,
                            $member->user->email,
                            $member->organization_id,
                            $member->billable_rate ?? '',
                            $member->role,
                            $member->created_at?->toIso8601ZuluString() ?? '',
                            $member->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Tasks
            $writer = Writer::createFromPath($temporaryDirectory->path('tasks.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'name',
                'project_id',
                'organization_id',
                'done_at',
                'created_at',
                'updated_at',
            ]);
            Task::query()
                ->whereBelongsTo($organization, 'organization')
                ->chunk(1000, function (Collection $tasks) use (&$writer): void {
                    $tasks->each(function (Task $task) use (&$writer): void {
                        $writer->insertOne([
                            $task->id,
                            $task->name,
                            $task->project_id,
                            $task->organization_id,
                            $task->done_at?->toIso8601ZuluString() ?? '',
                            $task->created_at?->toIso8601ZuluString() ?? '',
                            $task->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Tags
            $writer = Writer::createFromPath($temporaryDirectory->path('tags.csv'), 'w+');
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setEscape('');
            $writer->insertOne([
                'id',
                'name',
                'organization_id',
                'created_at',
                'updated_at',
            ]);
            Tag::query()
                ->whereBelongsTo($organization, 'organization')
                ->chunk(1000, function (Collection $tags) use (&$writer): void {
                    $tags->each(function (Tag $tag) use (&$writer): void {
                        $writer->insertOne([
                            $tag->id,
                            $tag->name,
                            $tag->organization_id,
                            $tag->created_at?->toIso8601ZuluString() ?? '',
                            $tag->updated_at?->toIso8601ZuluString() ?? '',
                        ]);
                    });
                });

            // Meta data file
            $metaData = (object) [
                'id' => $exportId,
                'version' => self::VERSION,
                'organizations' => [$organization->getKey()],
                'exported_at' => $timeStamp->toIso8601ZuluString(),
            ];
            file_put_contents($temporaryDirectory->path('meta.json'), json_encode($metaData));

            // Create ZIP file
            $temporaryDirectoryZip = TemporaryDirectory::make();
            $zip = new ZipArchive;
            if ($zip->open($temporaryDirectoryZip->path('export.zip'), ZipArchive::CREATE) !== true) {
                throw new Exception('Cannot create ZIP file');
            }
            $zip->addFile($temporaryDirectory->path('organizations.csv'), 'organizations.csv');
            $zip->addFile($temporaryDirectory->path('organization_invitations.csv'), 'organization_invitations.csv');
            $zip->addFile($temporaryDirectory->path('time_entries.csv'), 'time_entries.csv');
            $zip->addFile($temporaryDirectory->path('clients.csv'), 'clients.csv');
            $zip->addFile($temporaryDirectory->path('projects.csv'), 'projects.csv');
            $zip->addFile($temporaryDirectory->path('project_members.csv'), 'project_members.csv');
            $zip->addFile($temporaryDirectory->path('members.csv'), 'members.csv');
            $zip->addFile($temporaryDirectory->path('tasks.csv'), 'tasks.csv');
            $zip->addFile($temporaryDirectory->path('tags.csv'), 'tags.csv');
            $zip->addFile($temporaryDirectory->path('meta.json'), 'meta.json');
            $zip->close();

            // Upload ZIP file to private storage
            $filename = 'export_'.$organization->getKey().'_'.$timeStamp->format('Y-m-d_H-i-s').'_'.$exportId.'.zip';
            Storage::disk(config('filesystems.private'))->putFileAs(
                'exports',
                new File($temporaryDirectoryZip->path('export.zip')),
                $filename
            );

            // Delete temp files
            $temporaryDirectoryZip->delete();
            $temporaryDirectory->delete();

            Log::debug('Finished exporting organization', [
                'organization_id' => $organization->getKey(),
                'export_id' => $exportId,
            ]);

            return 'exports/'.$filename;
        } catch (UnavailableStream|CannotInsertRecord|Exception|LeagueCsvException $exception) {
            report($exception);

            throw new ExportException;
        }
    }
}
