<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Detects imported time entries that already exist in the organization, so importing the same data twice does not
 * duplicate time entries.
 *
 * Matches are counted: if the organization already contains a time entry N times, the first N equal time entries of
 * an import are skipped and every further one is created. Re-importing the same data therefore creates nothing,
 * while identical time entries within one import are still kept.
 *
 * Existing time entries are loaded lazily per UTC month of their start, so only the months an import touches are
 * queried. A month is always loaded before the first time entry of that month is created by the import, so time
 * entries created by the import itself are never counted as existing.
 */
class TimeEntryDeduplicator
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Remaining number of existing time entries per fingerprint, grouped by month ("Y-m")
     *
     * @var array<string, array<string, int>>
     */
    private array $existingCountsByMonth = [];

    public function __construct(private readonly string $organizationId) {}

    /**
     * Returns true if an equal time entry already exists in the organization and was not matched by an earlier
     * time entry of this import. The match is consumed, so the caller should skip creating the time entry.
     */
    public function claimExisting(TimeEntry $timeEntry): bool
    {
        // Note: The raw attributes contain the timestamps exactly as they will be stored (UTC, "Y-m-d H:i:s"). Reading
        // them directly is also much faster than going through the datetime casts.
        $attributes = $timeEntry->getAttributes();
        $start = $attributes['start'] ?? null;
        $end = $attributes['end'] ?? null;
        if (! is_string($start) || ! is_string($end)) {
            return false;
        }

        $month = substr($start, 0, 7);
        if (! isset($this->existingCountsByMonth[$month])) {
            $this->existingCountsByMonth[$month] = $this->loadMonth($month);
        }

        $fingerprint = $this->getFingerprint(
            $timeEntry->member_id,
            $start,
            $end,
            $timeEntry->project_id,
            $timeEntry->task_id,
            $timeEntry->description
        );

        $remaining = $this->existingCountsByMonth[$month][$fingerprint] ?? 0;
        if ($remaining === 0) {
            return false;
        }
        $this->existingCountsByMonth[$month][$fingerprint] = $remaining - 1;

        return true;
    }

    /**
     * @param  string  $month  Month in the format "Y-m"
     * @return array<string, int>
     */
    private function loadMonth(string $month): array
    {
        $monthStart = Carbon::createFromFormat('Y-m-d H:i:s', $month.'-01 00:00:00', 'UTC');
        if ($monthStart === null) {
            throw new RuntimeException('Invalid month');
        }
        $monthEnd = $monthStart->copy()->addMonth();

        $counts = [];
        // The query builder (without models) returns the timestamps exactly as stored, so they can be compared with
        // the raw attributes of the imported time entries.
        $rows = TimeEntry::query()
            ->where('organization_id', '=', $this->organizationId)
            ->where('start', '>=', $monthStart->format(self::DATE_FORMAT))
            ->where('start', '<', $monthEnd->format(self::DATE_FORMAT))
            ->whereNotNull('end')
            ->toBase()
            ->select(['member_id', 'start', 'end', 'project_id', 'task_id', 'description'])
            ->get();

        foreach ($rows as $row) {
            /** @var object{member_id: string, start: string, end: string, project_id: string|null, task_id: string|null, description: string} $row */
            $fingerprint = $this->getFingerprint(
                $row->member_id,
                $row->start,
                $row->end,
                $row->project_id,
                $row->task_id,
                $row->description
            );
            $counts[$fingerprint] = ($counts[$fingerprint] ?? 0) + 1;
        }

        return $counts;
    }

    private function getFingerprint(string $memberId, string $start, string $end, ?string $projectId, ?string $taskId, string $description): string
    {
        $json = json_encode([$memberId, $start, $end, $projectId, $taskId, $description]);
        if ($json === false) {
            throw new RuntimeException('Failed to encode time entry fingerprint');
        }

        return md5($json);
    }
}
