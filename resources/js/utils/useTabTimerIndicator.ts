import { computed, onScopeDispose, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { formatDuration } from '@/packages/ui/src/utils/time';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';

/**
 * Keeps the browser tab in sync with the running timer so you can tell at a
 * glance whether solidtime is still tracking, even when the tab is in the
 * background:
 *
 *  - the tab title shows the running duration and the entry description
 *  - the favicon gets a colored dot (green while working, amber during a break)
 *
 * When no timer is running the original title and favicons are left untouched.
 */

export type TabTimerIndicator = 'idle' | 'work' | 'break';

type TabTimerState = {
    indicator: TabTimerIndicator;
    durationSeconds: number;
    description: string | null | undefined;
};

const DOT_COLOR: Record<Exclude<TabTimerIndicator, 'idle'>, string> = {
    work: '#22c55e',
    break: '#f59e0b',
};

const BASE_FAVICON_URL = '/favicons/favicon-32x32.png';
const DYNAMIC_FAVICON_ATTR = 'data-dynamic-favicon';

export function resolveTabTimerIndicator(isActive: boolean, isOnBreak: boolean): TabTimerIndicator {
    if (!isActive) {
        return 'idle';
    }
    return isOnBreak ? 'break' : 'work';
}

export function buildTabTitle(state: TabTimerState, baseTitle: string): string {
    if (state.indicator === 'idle') {
        return baseTitle;
    }
    const time = formatDuration(Math.max(0, Math.floor(state.durationSeconds)));
    if (state.indicator === 'break') {
        return `☕ ${time} · Break`;
    }
    const description = state.description?.trim();
    return `${time} · ${description ? description : 'No description'}`;
}

let stashedFavicons: HTMLLinkElement[] = [];

function applyDynamicFavicon(dataUri: string) {
    const existing = document.head.querySelector<HTMLLinkElement>(`link[${DYNAMIC_FAVICON_ATTR}]`);
    if (existing) {
        existing.href = dataUri;
        return;
    }
    stashedFavicons = Array.from(
        document.head.querySelectorAll<HTMLLinkElement>('link[rel~="icon"]')
    );
    stashedFavicons.forEach((link) => link.remove());

    const link = document.createElement('link');
    link.rel = 'icon';
    link.type = 'image/png';
    link.setAttribute(DYNAMIC_FAVICON_ATTR, 'true');
    link.href = dataUri;
    document.head.appendChild(link);
}

function restoreFavicon() {
    const dynamic = document.head.querySelectorAll(`link[${DYNAMIC_FAVICON_ATTR}]`);
    if (dynamic.length === 0 && stashedFavicons.length === 0) {
        return;
    }
    dynamic.forEach((link) => link.remove());
    stashedFavicons.forEach((link) => document.head.appendChild(link));
    stashedFavicons = [];
}

async function renderFaviconWithDot(color: string): Promise<string | null> {
    const size = 32;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return null;
    }

    await new Promise<void>((resolve) => {
        const image = new Image();
        image.onload = () => {
            ctx.drawImage(image, 0, 0, size, size);
            resolve();
        };
        image.onerror = () => resolve();
        image.src = BASE_FAVICON_URL;
    });

    const radius = 7;
    const center = size - radius - 1;

    ctx.beginPath();
    ctx.arc(center, center, radius + 2, 0, Math.PI * 2);
    ctx.fillStyle = '#ffffff';
    ctx.fill();

    ctx.beginPath();
    ctx.arc(center, center, radius, 0, Math.PI * 2);
    ctx.fillStyle = color;
    ctx.fill();

    return canvas.toDataURL('image/png');
}

export function useTabTimerIndicator() {
    const store = useCurrentTimeEntryStore();
    const { currentTimeEntry, now, isActive, isOnBreak } = storeToRefs(store);

    const baseTitle = ref(document.title);

    const indicator = computed(() => resolveTabTimerIndicator(isActive.value, isOnBreak.value));

    const durationSeconds = computed(() => {
        if (!now.value || !currentTimeEntry.value.start) {
            return 0;
        }
        return now.value.diff(dayjs(currentTimeEntry.value.start), 's');
    });

    function applyTitle() {
        document.title = buildTabTitle(
            {
                indicator: indicator.value,
                durationSeconds: durationSeconds.value,
                description: currentTimeEntry.value.description,
            },
            baseTitle.value
        );
    }

    // Inertia rewrites document.title on every page visit. Re-capture it while
    // idle, and re-apply the timer title right after navigation otherwise.
    const stopNavigateListener = router.on('navigate', () => {
        window.requestAnimationFrame(() => {
            if (indicator.value === 'idle') {
                baseTitle.value = document.title;
            } else {
                applyTitle();
            }
        });
    });

    watch([indicator, durationSeconds], applyTitle, { immediate: true });

    watch(
        indicator,
        async (value) => {
            if (value === 'idle') {
                restoreFavicon();
                return;
            }
            const dataUri = await renderFaviconWithDot(DOT_COLOR[value]);
            if (dataUri) {
                applyDynamicFavicon(dataUri);
            }
        },
        { immediate: true }
    );

    onScopeDispose(() => {
        stopNavigateListener();
        restoreFavicon();
        document.title = baseTitle.value;
    });
}
