import { computed, ref } from 'vue';
import type { Dayjs } from 'dayjs';
import { getLocalizedDayJs } from '../utils/time';
import { getWeekStart } from '../utils/settings';

export function useCalendarNavigation(callbacks: {
    onDatesChange: (payload: { start: Date; end: Date }) => void;
    scrollToCurrentTime: () => void;
}) {
    const activeView = ref('timeGridWeek');
    const currentDate = ref(getLocalizedDayJs());

    function getFirstDay(): number {
        const weekStart = getWeekStart();
        const weekStartMap: Record<string, number> = {
            sunday: 0,
            monday: 1,
            tuesday: 2,
            wednesday: 3,
            thursday: 4,
            friday: 5,
            saturday: 6,
        };
        return weekStartMap[weekStart] ?? 1;
    }

    const viewDays = computed<Dayjs[]>(() => {
        const numDays = activeView.value === 'timeGridWeek' ? 7 : 1;

        if (numDays === 1) {
            return [currentDate.value.startOf('day')];
        }

        const firstDay = getFirstDay();
        const today = currentDate.value.startOf('day');
        const offset = (today.day() - firstDay + 7) % 7;
        const weekStart = today.subtract(offset, 'day');

        const days: Dayjs[] = [];
        for (let i = 0; i < numDays; i++) {
            days.push(weekStart.add(i, 'day'));
        }
        return days;
    });

    const viewTitle = computed(() => {
        if (activeView.value === 'timeGridDay') {
            return currentDate.value.format('MMMM YYYY');
        }

        const days = viewDays.value;
        if (days.length === 0) return '';

        const first = days[0]!;
        const last = days[days.length - 1]!;

        if (first.year() !== last.year()) {
            return `${first.format('MMM YYYY')} \u2013 ${last.format('MMM YYYY')}`;
        }
        if (first.month() !== last.month()) {
            return `${first.format('MMM')} \u2013 ${last.format('MMM YYYY')}`;
        }
        return first.format('MMMM YYYY');
    });

    function emitDatesChange() {
        const days = viewDays.value;
        if (days.length === 0) return;

        const start = days[0]!.toDate();
        const end = days[days.length - 1]!.add(1, 'day').toDate();
        callbacks.onDatesChange({ start, end });
    }

    function handlePrev() {
        if (activeView.value === 'timeGridWeek') {
            currentDate.value = currentDate.value.subtract(7, 'day');
        } else {
            currentDate.value = currentDate.value.subtract(1, 'day');
        }
        emitDatesChange();
        callbacks.scrollToCurrentTime();
    }

    function handleNext() {
        if (activeView.value === 'timeGridWeek') {
            currentDate.value = currentDate.value.add(7, 'day');
        } else {
            currentDate.value = currentDate.value.add(1, 'day');
        }
        emitDatesChange();
        callbacks.scrollToCurrentTime();
    }

    function handleToday() {
        currentDate.value = getLocalizedDayJs();
        emitDatesChange();
        callbacks.scrollToCurrentTime();
    }

    function handleChangeView(view: string) {
        activeView.value = view;
        emitDatesChange();
        callbacks.scrollToCurrentTime();
    }

    return {
        activeView,
        currentDate,
        viewDays,
        viewTitle,
        emitDatesChange,
        handlePrev,
        handleNext,
        handleToday,
        handleChangeView,
    };
}
