<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Task;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use App\Models\Project;
use App\Service\PermissionStore;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class TaskIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'page' => [
                'integer',
                'min:1',
                'max:2147483647',
            ],
            'project_id' => [
                ExistsEloquent::make(Project::class, null, function (Builder $builder): Builder {
                    /** @var Builder<Project> $builder */
                    $builder = $builder->whereBelongsTo($this->organization, 'organization');

                    if (! app(PermissionStore::class)->has($this->organization, 'tasks:view:all')) {
                        $builder = $builder->visibleByEmployee(Auth::user());
                    }

                    return $builder;
                })->uuid(),
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
