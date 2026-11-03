<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Import;

use App\Http\Resources\V1\BaseResource;
use App\Models\Import;
use Illuminate\Http\Request;

/**
 * @property Import $resource
 */
class ImportResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID of the import */
            'id' => $this->resource->id,
            /** Importer key, see the importers endpoint */
            'type' => $this->resource->type,
            /** Source of the import data */
            'source' => $this->resource->source,
            /** Status of the import */
            'status' => $this->resource->status,
            /** @var array{
             *   clients: array{
             *     created: int,
             *   },
             *   projects: array{
             *     created: int,
             *   },
             *   tasks: array{
             *     created: int,
             *   },
             *   time_entries: array{
             *     created: int,
             *   },
             *   tags: array{
             *     created: int,
             *   },
             *   users: array{
             *     created: int,
             *   }
             * }|null $report Import report, only set if the import is completed */
            'report' => $this->resource->report,
            /** Error message, only set if the import failed */
            'error_message' => $this->resource->error_message,
            /** ID of the user that started the import */
            'user_id' => $this->resource->user_id,
            /** @var string|null $started_at Date when the import was started */
            'started_at' => $this->formatDateTime($this->resource->started_at),
            /** @var string|null $finished_at Date when the import was finished */
            'finished_at' => $this->formatDateTime($this->resource->finished_at),
            /** @var string|null $created_at Date when the import was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
