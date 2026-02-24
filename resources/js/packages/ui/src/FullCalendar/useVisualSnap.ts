import { onActivated, onDeactivated, onMounted, onUnmounted, type Ref } from 'vue';
import type FullCalendar from '@fullcalendar/vue3';

interface VisualSnapOptions {
    calendarRef: Ref<InstanceType<typeof FullCalendar> | null>;
    snapMinutes: () => number;
    slotMinutes: () => number;
    formatDuration: (durationSeconds: number) => string;
}

export function useVisualSnap({
    calendarRef,
    snapMinutes,
    slotMinutes,
    formatDuration,
}: VisualSnapOptions) {
    let rafId: number | null = null;

    function getCalendarEl(): HTMLElement | null {
        return (calendarRef.value?.$el as HTMLElement) ?? null;
    }

    function getSnapPixels(): number {
        const calendarEl = getCalendarEl();
        if (!calendarEl) return 25;
        const slot = calendarEl.querySelector('.fc-timegrid-slot-lane') as HTMLElement;
        if (!slot) return 25;
        const slotHeightPx = slot.getBoundingClientRect().height;
        return (snapMinutes() / slotMinutes()) * slotHeightPx;
    }

    function findMirrorHarness(calendarEl: HTMLElement) {
        const mirror = calendarEl.querySelector('.fc-event-mirror') as HTMLElement | null;
        const harness = mirror?.closest('.fc-timegrid-event-harness') as HTMLElement | null;
        if (harness) {
            harness.style.pointerEvents = 'none';
        }
        return { mirror, harness };
    }

    function updateMirrorDurationLabel(
        mirror: HTMLElement,
        snappedTop: number,
        snappedEnd: number,
        snapPx: number
    ) {
        const snappedDurationMin = Math.round((snappedEnd - snappedTop) / snapPx) * snapMinutes();
        const durationText = formatDuration(snappedDurationMin * 60);
        const durationEl = mirror.querySelector('.fc-event-main')?.querySelector('div:last-child');
        if (durationEl) {
            durationEl.textContent = durationText;
        }
    }

    function startLoop(onFrame: (calendarEl: HTMLElement, snapPx: number) => void) {
        const calendarEl = getCalendarEl();
        if (!calendarEl) return;
        const snapPx = getSnapPixels();
        if (snapPx <= 0) return;

        const loop = () => {
            onFrame(calendarEl, snapPx);
            rafId = requestAnimationFrame(loop);
        };
        rafId = requestAnimationFrame(loop);
    }

    function stop() {
        document.body.classList.remove('fc-resizing-active');
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    }

    // --- Public snap starters ---

    function startSelectSnap() {
        // Don't start if another snap loop is already running
        if (rafId !== null) return;
        startLoop((calendarEl, snapPx) => {
            const { mirror, harness } = findMirrorHarness(calendarEl);
            if (!harness || !mirror) return;

            const top = parseFloat(harness.style.top) || 0;
            const endPos = -(parseFloat(harness.style.bottom) || 0);
            const snappedTop = Math.floor(top / snapPx) * snapPx;
            const snappedEnd = Math.ceil(endPos / snapPx) * snapPx;
            const clampedEnd = Math.max(snappedTop + snapPx, snappedEnd);
            harness.style.top = snappedTop + 'px';
            harness.style.bottom = -clampedEnd + 'px';
            updateMirrorDurationLabel(mirror, snappedTop, clampedEnd, snapPx);
        });
    }

    function startDragSnap() {
        stop();
        startLoop((calendarEl, snapPx) => {
            const { harness } = findMirrorHarness(calendarEl);
            if (!harness) return;

            const top = parseFloat(harness.style.top) || 0;
            const endPos = -(parseFloat(harness.style.bottom) || 0);
            const height = endPos - top;
            const snappedTop = Math.floor(top / snapPx) * snapPx;
            harness.style.top = snappedTop + 'px';
            harness.style.bottom = -(snappedTop + height) + 'px';
        });
    }

    function startResizeSnap() {
        stop();
        document.body.classList.add('fc-resizing-active');

        let initialTop: number | null = null;
        let initialEnd: number | null = null;
        let resizeEdge: 'top' | 'bottom' | null = null;

        startLoop((calendarEl, snapPx) => {
            const { mirror, harness } = findMirrorHarness(calendarEl);
            if (!harness) return;

            const top = parseFloat(harness.style.top) || 0;
            const endPos = -(parseFloat(harness.style.bottom) || 0);

            // Detect which edge is being resized
            if (initialTop === null) {
                initialTop = top;
                initialEnd = endPos;
            } else if (resizeEdge === null) {
                const topDelta = Math.abs(top - initialTop);
                const endDelta = Math.abs(endPos - initialEnd!);
                if (topDelta > 0.5) {
                    resizeEdge = 'top';
                } else if (endDelta > 0.5) {
                    resizeEdge = 'bottom';
                }
            }

            if (resizeEdge === 'bottom') {
                const snappedEnd = Math.ceil(endPos / snapPx) * snapPx;
                const clampedEnd = Math.max(top + snapPx, snappedEnd);
                harness.style.bottom = -clampedEnd + 'px';
                if (mirror) updateMirrorDurationLabel(mirror, top, clampedEnd, snapPx);
            } else if (resizeEdge === 'top') {
                const snappedTop = Math.floor(top / snapPx) * snapPx;
                const clampedTop = Math.min(endPos - snapPx, snappedTop);
                harness.style.top = clampedTop + 'px';
                if (mirror) updateMirrorDurationLabel(mirror, clampedTop, endPos, snapPx);
            }
        });
    }

    // Pointerdown handler for starting select snap on timegrid background
    function handleTimegridPointerDown(e: PointerEvent) {
        const target = e.target as HTMLElement;
        if (target.closest('.fc-event')) return;
        startSelectSnap();
    }

    // Lifecycle: attach/detach pointerdown listener
    function attachListener() {
        const calendarEl = getCalendarEl();
        calendarEl?.addEventListener('pointerdown', handleTimegridPointerDown);
    }

    function detachListener() {
        const calendarEl = getCalendarEl();
        calendarEl?.removeEventListener('pointerdown', handleTimegridPointerDown);
    }

    onMounted(attachListener);
    onActivated(attachListener);
    onDeactivated(() => {
        stop();
        detachListener();
    });
    onUnmounted(() => {
        stop();
        detachListener();
    });

    return {
        startSelectSnap,
        startDragSnap,
        startResizeSnap,
        stop,
    };
}
