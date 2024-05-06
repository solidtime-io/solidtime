<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TimeEntryFilter
{
    /**
     * @var Builder<TimeEntry>
     */
    private Builder $builder;

    /**
     * @param  Builder<TimeEntry>  $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function addBeforeFilter(?string $dateTime): self
    {
        if ($dateTime === null) {
            return $this;
        }
        $this->builder->where('start', '<', Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $dateTime, 'UTC'));

        return $this;
    }

    public function addAfterFilter(?string $dateTime): self
    {
        if ($dateTime === null) {
            return $this;
        }
        $this->builder->where('start', '>', Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $dateTime, 'UTC'));

        return $this;
    }

    public function addActiveFilter(?string $active): self
    {
        if ($active === null) {
            return $this;
        }
        if ($active === 'true') {
            $this->builder->whereNull('end');
        }
        if ($active === 'false') {
            $this->builder->whereNotNull('end');
        }

        return $this;
    }

    public function addUserIdFilter(?string $userId): self
    {
        if ($userId === null) {
            return $this;
        }
        $this->builder->where('user_id', $userId);

        return $this;
    }

    public function addBillableFilter(?string $billable): self
    {
        if ($billable === null) {
            return $this;
        }
        if ($billable === 'true') {
            $this->builder->where('billable', '=', true);
        } elseif ($billable === 'false') {
            $this->builder->where('billable', '=', false);
        } else {
            Log::warning('Invalid billable filter value', ['value' => $billable]);
        }

        return $this;
    }

    /**
     * @param  array<string>|null  $clientIds
     */
    public function addClientIdsFilter(?array $clientIds): self
    {
        if ($clientIds === null) {
            return $this;
        }
        $this->builder->whereIn('client_id', $clientIds);

        return $this;
    }

    /**
     * @param  array<string>|null  $projectIds
     */
    public function addProjectIdsFilter(?array $projectIds): self
    {
        if ($projectIds === null) {
            return $this;
        }
        $this->builder->whereIn('project_id', $projectIds);

        return $this;
    }

    /**
     * @param  array<string>|null  $tagIds
     */
    public function addTagIdsFilter(?array $tagIds): self
    {
        if ($tagIds === null) {
            return $this;
        }
        $this->builder->whereJsonContains('tags', $tagIds);

        return $this;
    }

    /**
     * @param  array<string>|null  $taskIds
     */
    public function addTaskIdsFilter(?array $taskIds): self
    {
        if ($taskIds === null) {
            return $this;
        }
        $this->builder->whereIn('task_id', $taskIds);

        return $this;
    }

    /**
     * @return Builder<TimeEntry>
     */
    public function get(): Builder
    {
        return $this->builder;
    }
}
