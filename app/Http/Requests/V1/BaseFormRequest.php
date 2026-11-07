<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Models\Organization;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

class BaseFormRequest extends FormRequest
{
    /**
     * @return list<string>
     */
    protected function moneyRules(bool $bigInt = false): array
    {
        $rules = [
            'integer',
            'min:0',
        ];
        if ($bigInt) {
            $rules[] = 'max:9223372036854775807';
        } else {
            $rules[] = 'max:2147483647';
        }

        return $rules;
    }

    /**
     * Validates that the task belongs to the project given in the input field $projectKey.
     * The check is skipped if the project ID is not a valid UUID, since the project ID rule already reports that error
     * and the query would otherwise fail in the database.
     */
    protected function taskBelongsToProjectRule(Organization $organization, string $projectKey): ConditionalRules
    {
        $projectId = $this->input($projectKey);

        return Rule::when($projectId === null || Str::isUuid($projectId), [
            ExistsEloquent::make(Task::class, null, function (Builder $builder) use ($organization, $projectId): Builder {
                /** @var Builder<Task> $builder */
                return $builder->whereBelongsTo($organization, 'organization')
                    ->where('project_id', $projectId);
            })->uuid()->withMessage(__('validation.task_belongs_to_project')),
        ]);
    }
}
