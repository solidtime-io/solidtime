<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Member;
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

    public function addEndFilter(?string $dateTime): self
    {
        if ($dateTime === null) {
            return $this;
        }
        $this->addEnd(Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $dateTime, 'UTC'));

        return $this;
    }

    public function addEnd(?Carbon $end): self
    {
        if ($end === null) {
            return $this;
        }
        $this->builder->where('start', '<', $end);

        return $this;
    }

    public function addStartFilter(?string $dateTime): self
    {
        if ($dateTime === null) {
            return $this;
        }
        $this->addStart(Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $dateTime, 'UTC'));

        return $this;
    }

    public function addStart(?Carbon $start): self
    {
        if ($start === null) {
            return $this;
        }
        $this->builder->where('start', '>', $start);

        return $this;
    }

    public function addActiveFilter(?string $active): self
    {
        if ($active === null) {
            return $this;
        }
        if ($active === 'true') {
            $this->addActive(true);
        } elseif ($active === 'false') {
            $this->addActive(false);
        } else {
            Log::warning('Invalid active filter value', ['value' => $active]);
        }

        return $this;
    }

    public function addActive(?bool $active): self
    {
        if ($active) {
            $this->builder->whereNull('end');
        } else {
            $this->builder->whereNotNull('end');
        }

        return $this;
    }

    public function addMemberIdFilter(?Member $member): self
    {
        if ($member === null) {
            return $this;
        }
        $this->builder->where('member_id', $member->getKey());

        return $this;
    }

    /**
     * @param  array<string>|null  $memberIds
     */
    public function addMemberIdsFilter(?array $memberIds): self
    {
        if ($memberIds === null) {
            return $this;
        }
        $this->builder->whereIn('member_id', $memberIds);

        return $this;
    }

    public function addBillableFilter(?string $billable): self
    {
        if ($billable === null) {
            return $this;
        }
        if ($billable === 'true') {
            $this->addBillable(true);
        } elseif ($billable === 'false') {
            $this->addBillable(false);
        } else {
            Log::warning('Invalid billable filter value', ['value' => $billable]);
        }

        return $this;
    }

    public function addBillable(?bool $billable): self
    {
        if ($billable === null) {
            return $this;
        }
        $this->builder->where('billable', '=', $billable);

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
        $this->builder->where(function (Builder $builder) use ($tagIds): void {
            foreach ($tagIds as $tagId) {
                $builder->orWhereJsonContains('tags', $tagId);
            }
        });

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
