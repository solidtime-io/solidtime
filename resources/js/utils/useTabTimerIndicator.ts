import { computed, effectScope, watch } from 'vue';
import { storeToRefs } from 'pinia';
import dayjs from 'dayjs';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { formatDuration } from '@/packages/ui/src/utils/time';

type IndicatorState = 'idle' | 'working' | 'break';

/**
 * The favicon is always loaded from /favicon.ico on the app's own origin, never from the href of the
 * <link rel="icon"> tags: with a whitelabel favicon those point to the public disk (e.g. S3), and a canvas
 * can not export an image from another origin. FaviconController serves the default favicon and whitelabel
 * favicons on the public disk itself, so the image is same-origin.
 */
const FAVICON_PATH = '/favicon.ico';
const SIZE = 64;
const DOT_COLORS: Record<Exclude<IndicatorState, 'idle'>, string> = {
    working: '#ef4444',
    break: '#f59e0b',
};
const TITLE_SEPARATOR = ' · ';

let baseImage: Promise<HTMLImageElement> | null = null;
const drawn = new Map<IndicatorState, string | null>();

/**
 * Browsers cache /favicon.ico for a day. The href of the favicon link changes whenever the favicon changes
 * (another file or a new asset version), so it is used as cache buster.
 */
function faviconUrl(linkHref: string): string {
    return `${FAVICON_PATH}?v=${encodeURIComponent(linkHref)}`;
}

function loadBaseImage(linkHref: string): Promise<HTMLImageElement> {
    baseImage ??= new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = faviconUrl(linkHref);
    });

    return baseImage;
}

/**
 * The favicon with a dot in the bottom right corner, null if it can not be drawn
 * (e.g. a whitelabel favicon on an external URL, where /favicon.ico redirects to another origin).
 */
async function drawIndicator(linkHref: string, color: string): Promise<string | null> {
    try {
        const image = await loadBaseImage(linkHref);
        const canvas = document.createElement('canvas');
        canvas.width = SIZE;
        canvas.height = SIZE;
        const context = canvas.getContext('2d');
        if (context === null) {
            return null;
        }
        context.drawImage(image, 0, 0, SIZE, SIZE);

        // Cut out a ring around the dot, so it stays visible on any favicon
        const x = SIZE * 0.76;
        const y = SIZE * 0.76;
        context.globalCompositeOperation = 'destination-out';
        context.beginPath();
        context.arc(x, y, SIZE * 0.26, 0, Math.PI * 2);
        context.fill();
        context.globalCompositeOperation = 'source-over';
        context.beginPath();
        context.arc(x, y, SIZE * 0.19, 0, Math.PI * 2);
        context.fillStyle = color;
        context.fill();

        // Throws for a tainted canvas (image from another origin)
        return canvas.toDataURL('image/png');
    } catch {
        return null;
    }
}

// Title

// The title of the current page (set by Inertia), without the duration
let pageTitle: string | null = null;
// The running duration shown in front of the title, null if no timer runs
let titlePrefix: string | null = null;

function composeTitle(title: string): string {
    return titlePrefix === null ? title : titlePrefix + TITLE_SEPARATOR + title;
}

/**
 * Used in the title callback of the Inertia app, so a visit sets the title with the duration in one step.
 */
export function withTabTimer(title: string): string {
    pageTitle = title;

    return composeTitle(title);
}

let started = false;

/**
 * Shows the state of the current time entry in the browser tab:
 * - the title starts with the running duration (e.g. "01:23:45 · Dashboard - solidtime")
 * - the favicon gets a red dot while a timer runs and an amber dot during a break
 * Both are restored when the timer stops.
 *
 * Runs once per page load: pages render their own layout, so the layout is mounted again on every Inertia visit,
 * restoring and applying the indicator on each mount would make the title and the favicon flicker.
 */
export function useTabTimerIndicator() {
    if (started) {
        return;
    }
    started = true;
    effectScope(true).run(() => {
        const { isActive, isOnBreak, currentTimeEntry, now } = storeToRefs(
            useCurrentTimeEntryStore()
        );
        const state = computed<IndicatorState>(() =>
            isOnBreak.value ? 'break' : isActive.value ? 'working' : 'idle'
        );

        // Favicon

        const links = Array.from(document.querySelectorAll<HTMLLinkElement>('link[rel~="icon"]'));
        const originalHrefs = new Map(links.map((link) => [link, link.href]));
        const baseHref = links[0]?.href ?? '';

        function restoreFavicon() {
            originalHrefs.forEach((href, link) => {
                link.href = href;
            });
        }

        let latest = 0;
        async function applyFavicon(newState: IndicatorState) {
            const request = ++latest;
            if (newState === 'idle') {
                restoreFavicon();
                return;
            }
            if (!drawn.has(newState)) {
                drawn.set(newState, await drawIndicator(baseHref, DOT_COLORS[newState]));
            }
            const href = drawn.get(newState);
            // Ignore results of a state that changed while the image was loading
            if (request !== latest) {
                return;
            }
            if (href === null || href === undefined) {
                restoreFavicon();
                return;
            }
            links.forEach((link) => {
                link.href = href;
            });
        }

        watch(state, applyFavicon, { immediate: true });

        // Title

        const prefix = computed<string | null>(() => {
            if (state.value === 'idle' || now.value === null || !currentTimeEntry.value.start) {
                return null;
            }
            const seconds = Math.max(0, now.value.diff(dayjs(currentTimeEntry.value.start), 's'));
            const duration = formatDuration(seconds);

            return state.value === 'break' ? `Break ${duration}` : duration;
        });

        watch(
            prefix,
            (newPrefix) => {
                titlePrefix = newPrefix;
                pageTitle ??= document.title;
                const title = composeTitle(pageTitle);
                if (document.title !== title) {
                    document.title = title;
                }
            },
            { immediate: true }
        );
    });
}
