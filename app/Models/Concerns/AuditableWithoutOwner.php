<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Marks an auditable model whose audits intentionally have no owner (neither an organization nor a user),
 * either because the model belongs to neither of them, or because the model and its audits have to be kept
 * when its organization or user is deleted (for example billing records).
 * Audits of these models are not deleted together with an organization or user,
 * and are not considered as missing an owner (for example by the audit backfill command).
 */
interface AuditableWithoutOwner {}
