<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Task;

use App\Models\Organization;
use App\Models\Project;
use App\Service\PermissionStore;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TaskIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'uuid',
                new ExistsEloquent(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    $builder = $builder->whereBelongsTo($this->organization, 'organization');

                    if (! app(PermissionStore::class)->has($this->organization, 'tasks:view:all')) {
                        $builder = $builder->visibleByEmployee(Auth::user());
                    }

                    return $builder;
                }),
            ],
            'done' => [
                'string',
                'in:true,false,all',
            ],
        ];
    }

    public function getFilterDone(): string
    {
        return $this->input('done', 'false');
    }
}
