<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\V1\Report\DetailedReportResource;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Get report by a share secret
     *
     * This endpoint is public and does not require authentication. The report must be public and not expired.
     * The report is considered expired if the `public_until` field is set and the date is in the past.
     * The report is considered public if the `is_public` field is set to `true`.
     *
     * @operationId getPublicReport
     */
    public function show(Request $request): DetailedReportResource
    {
        $shareSecret = $request->header('X-Api-Key');
        if (! is_string($shareSecret)) {
            throw new ModelNotFoundException;
        }

        $report = Report::query()
            ->where('share_secret', '=', $shareSecret)
            ->where('is_public', '=', true)
            ->where(function (Builder $builder): void {
                /** @var Builder<Report> $builder */
                $builder->whereNull('public_until')
                    ->orWhere('public_until', '>', now());
            })
            ->firstOrFail();

        return new DetailedReportResource($report);
    }
}
