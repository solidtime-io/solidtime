<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;

trait CustomAuditable
{
    use Auditable;

    /**
     * @var array<string>|null
     */
    protected ?array $auditEvents = null;

    public function disableAuditing(): void
    {
        $this->auditEvents = [];
    }

    /**
     * The organization that owns the audited model.
     * The audits of the model are deleted (via foreign key cascade) when the organization is deleted.
     */
    public function getAuditOwnerOrganizationId(): ?string
    {
        return $this->getAttributes()['organization_id'] ?? null;
    }

    /**
     * The user that owns the audited model.
     * The audits of the model are deleted (via foreign key cascade) when the user is deleted.
     */
    public function getAuditOwnerUserId(): ?string
    {
        return null;
    }

    /**
     * Models that are the owner of their own audits can not record the deletion audit,
     * since the audit would reference the already deleted model and therefore violate the foreign key.
     */
    protected function isAuditOwnerOfItself(): bool
    {
        return false;
    }

    /**
     * @return array<int|string, string>
     */
    public function getAuditEvents(): array
    {
        $events = $this->auditEvents ?? Config::get('audit.events', [
            'created',
            'updated',
            'deleted',
            'restored',
        ]);

        if ($this->isAuditOwnerOfItself()) {
            $events = array_filter($events, fn (string $value, int|string $key): bool => (is_int($key) ? $value : $key) !== 'deleted', ARRAY_FILTER_USE_BOTH);
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        $data['owner_organization_id'] = $this->getAuditOwnerOrganizationId();
        $data['owner_user_id'] = $this->getAuditOwnerUserId();

        return $data;
    }
}
