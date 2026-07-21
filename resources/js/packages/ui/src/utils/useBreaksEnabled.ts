import { computed, inject, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';

/**
 * Whether break tracking is enabled for the current organization.
 *
 * Components below the app layout can call this with no argument (the layout
 * provides `organization`); pages that sit above the layout pass their own
 * organization ref. Without an organization (e.g. public report views) breaks
 * count as disabled.
 */
export function useBreaksEnabled(organization?: {
    value: Organization | undefined | null;
}): ComputedRef<boolean> {
    const org =
        organization ??
        inject<ComputedRef<Organization | undefined> | undefined>('organization', undefined);
    return computed(() => org?.value?.breaks_enabled ?? false);
}
