<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Task;

use App\Models\Organization;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 * @property Task $task Task from model binding
 */
class TaskUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                (new UniqueEloquent(Task::class, 'name', function (Builder $builder): Builder {
                    /** @var Builder<Task> $builder */
                    return $builder->where('project_id', '=', $this->task->project_id);
                }))->ignore($this->task?->getKey())->withCustomTranslation('validation.task_name_already_exists'),
            ],
            'is_done' => [
                'boolean',
            ],
        ];
    }

    public function getIsDone(): bool
    {
        assert($this->has('is_done'));

        return $this->boolean('is_done');
    }
}
