<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Goal;

use App\Enums\GoalComparison;
use App\Enums\GoalPeriod;
use App\Enums\Weekday;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Goal;
use App\Models\Organization;
use Illuminate\Contracts\Validation\Rule as LegacyValidationRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization Organization from model binding
 */
class GoalUpdateRequest extends BaseFormRequest
{
    use GoalFilterRules;

    /**
     * Get the validation rules that apply to the request.
     * The type of a goal and the member it is for can not be changed after creation.
     *
     * @return array<string, array<string|ValidationRule|LegacyValidationRule|\Closure>>
     */
    public function rules(): array
    {
        /** @var Goal $goal */
        $goal = $this->route('goal');

        return array_merge([
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            // Whether the goal is reached when "at least" or "less than" the target time is tracked in the period
            'comparison' => [
                'sometimes',
                'string',
                Rule::enum(GoalComparison::class),
            ],
            // Target time in seconds
            'target_seconds' => [
                'sometimes',
                'integer',
                'min:1',
                'max:2147483647',
            ],
            // The recurring time frame in which the target has to be reached
            'period' => [
                'sometimes',
                'string',
                Rule::enum(GoalPeriod::class),
            ],
            // Timezone that defines the periods of the goal
            'timezone' => [
                'sometimes',
                'timezone:all',
            ],
            // Week start that defines weekly periods
            'week_start' => [
                'sometimes',
                'string',
                Rule::enum(Weekday::class),
            ],
            // Archived goals are hidden from the goal list by default, their progress is still calculated
            'is_archived' => [
                'sometimes',
                'boolean',
            ],
            // Only allowed if it matches the current type of the goal
            'type' => [
                function (string $attribute, mixed $value, \Closure $fail) use ($goal): void {
                    if ($value !== $goal->type->value) {
                        $fail('The '.$attribute.' of a goal can not be changed.');
                    }
                },
            ],
            // Only allowed if it matches the current member of the goal
            'member_id' => [
                function (string $attribute, mixed $value, \Closure $fail) use ($goal): void {
                    if ($value !== $goal->member_id) {
                        $fail('The '.$attribute.' of a goal can not be changed.');
                    }
                },
            ],
        ], $this->filterRules($goal->member_id === null));
    }

    public function getName(): string
    {
        return (string) $this->input('name');
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

    public function getTimezone(): string
    {
        return (string) $this->input('timezone');
    }

    public function getWeekStart(): Weekday
    {
        return Weekday::from($this->input('week_start'));
    }

    public function getIsArchived(): bool
    {
        return (bool) $this->input('is_archived');
    }
}
