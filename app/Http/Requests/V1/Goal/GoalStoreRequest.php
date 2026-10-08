<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Goal;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\GoalType;
use App\Enums\Role;
use App\Enums\Weekday;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule as LegacyValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\ProhibitedIf;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class GoalStoreRequest extends BaseFormRequest
{
    use GoalFilterRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|LegacyValidationRule|In|ProhibitedIf|\Closure>>
     */
    public function rules(): array
    {
        $memberExistsInOrganization = ExistsEloquent::make(Member::class, null, function (Builder $builder): Builder {
            /** @var Builder<Member> $builder */
            return $builder->whereBelongsTo($this->organization, 'organization')
                ->where('role', '!=', Role::Placeholder->value);
        })->uuid();

        return array_merge([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            // personal: a goal for yourself that nobody else can see, organization: a goal of the organization for a member or for every member. Organization goals require the team goals extension.
            'type' => [
                'required',
                'string',
                Rule::enum(GoalType::class),
            ],
            // Whether the goal is reached when "at least" or "less than" the target time is tracked in the period
            'comparison' => [
                'required',
                'string',
                Rule::enum(GoalComparison::class),
            ],
            // Target time in seconds
            'target_seconds' => [
                'required',
                'integer',
                'min:1',
                'max:2147483647',
            ],
            // The recurring time frame in which the target has to be reached
            'period' => [
                'required',
                'string',
                Rule::enum(GoalPeriod::class),
            ],
            // ID of the member whose time entries count towards the goal, defaults to the current member. Send null for a goal that counts every member (organization goals only).
            'member_id' => $this->input('type') === GoalType::Personal->value
                // A personal goal is always for the current member, it can not count every member
                ? [
                    'sometimes',
                    'filled',
                    'string',
                    Rule::in([$this->currentMemberId()]),
                ]
                : [
                    'nullable',
                    'string',
                    $memberExistsInOrganization,
                ],
            // Timezone that defines the periods of the goal, defaults to the timezone of the current user
            'timezone' => [
                'nullable',
                'timezone:all',
            ],
            // Week start that defines weekly periods, defaults to the week start of the current user
            'week_start' => [
                'nullable',
                'string',
                Rule::enum(Weekday::class),
            ],
        ], $this->filterRules(
            // Without a member_id the goal is for the current member
            fn (): bool => $this->has('member_id') && $this->input('member_id') === null,
        ));
    }

    private function currentMemberId(): ?string
    {
        /** @var string|null $memberId */
        $memberId = Member::query()
            ->whereBelongsTo($this->organization, 'organization')
            ->where('user_id', $this->user()?->getKey())
            ->value('id');

        return $memberId;
    }

    public function getName(): string
    {
        return (string) $this->input('name');
    }

    public function getType(): GoalType
    {
        return GoalType::from($this->input('type'));
    }

    public function getComparison(): GoalComparison
    {
        return GoalComparison::from($this->input('comparison'));
    }

    public function getTargetSeconds(): int
    {
        return (int) $this->input('target_seconds');
    }

    public function getPeriod(): GoalPeriod
    {
        return GoalPeriod::from($this->input('period'));
    }

    public function hasMemberId(): bool
    {
        return $this->has('member_id');
    }

    public function getMemberId(): ?string
    {
        return $this->input('member_id');
    }

    public function getTimezone(): ?string
    {
        if (! $this->has('timezone') || $this->input('timezone') === null) {
            return null;
        }

        return (string) $this->input('timezone');
    }

    public function getWeekStart(): ?Weekday
    {
        if (! $this->has('week_start') || $this->input('week_start') === null) {
            return null;
        }

        return Weekday::from($this->input('week_start'));
    }
}
