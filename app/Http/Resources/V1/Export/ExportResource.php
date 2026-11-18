<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Export;

use App\Enums\ExportStatus;
use App\Http\Resources\V1\BaseResource;
use App\Models\Export;
use Illuminate\Http\Request;

/**
 * @property Export $resource
 */
class ExportResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** ID of the export */
            'id' => $this->resource->id,
            /** Type of the export */
            'type' => $this->resource->type,
            /** Status of the export */
            'status' => $this->resource->status,
            /** @var bool $download_available Whether the export file can be downloaded, the file is deleted after the retention period */
            'download_available' => $this->resource->status === ExportStatus::Completed && $this->resource->file_path !== null,
            /** Error message, only set if the export failed */
            'error_message' => $this->resource->error_message,
            /** ID of the user that started the export */
            'user_id' => $this->resource->user_id,
            /** @var string|null $started_at Date when the export was started */
            'started_at' => $this->formatDateTime($this->resource->started_at),
            /** @var string|null $finished_at Date when the export was finished */
            'finished_at' => $this->formatDateTime($this->resource->finished_at),
            /** @var string|null $created_at Date when the export was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
        ];
    }
}
