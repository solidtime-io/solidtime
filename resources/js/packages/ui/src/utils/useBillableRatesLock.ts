import { computed, inject, type ComputedRef, type InjectionKey } from 'vue';

export interface BillableRatesLock {
    locked: ComputedRef<boolean>;
    requestUpgrade: () => void;
}

export const billableRatesLockKey: InjectionKey<BillableRatesLock> = Symbol('billableRatesLock');

/**
 * Whether billable rates are locked behind a plan upgrade for the current organization.
 *
 * The app layout provides the lock state and opens the upgrade dialog. Without a
 * provider (e.g. public report views or other consumers of this package) billable
 * rates count as unlocked.
 */
export function useBillableRatesLock(): BillableRatesLock {
    return inject(billableRatesLockKey, {
        locked: computed(() => false),
        requestUpgrade: () => {},
    });
}
