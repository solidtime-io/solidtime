<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks an auditable model whose audits are owned by the owner of its parent model,
 * because the model itself has no organization_id (for example a project member belongs to an organization via its project).
 * The parent model has to have an organization_id column.
 * This is used to set the owner of new audits (CustomAuditable) and to backfill the owner of existing audits.
 */
interface AuditableThroughParent
{
    /**
     * @return BelongsTo<covariant Model, covariant Model>
     */
    public function getAuditParentRelation(): BelongsTo;
}
