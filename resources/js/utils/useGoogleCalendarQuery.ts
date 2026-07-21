import { useQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { computed, type ComputedRef, type Ref } from 'vue';
import type { Dayjs } from 'dayjs';
import { localDateToUtc } from '@/packages/ui/src/utils/time';

export type GoogleCalendarStatus = {
    available: boolean;
    connected: boolean;
    google_email: string | null;
};

export type GoogleCalendarEvent = {
    id: string;
    summary: string;
    start: string;
    end: string;
    all_day: boolean;
    html_link: string | null;
};

export function useGoogleCalendarStatusQuery() {
    return useQuery<GoogleCalendarStatus>({
        queryKey: ['googleCalendar', 'status'],
        queryFn: async () => {
            const response = await api.axios.get('/v1/users/me/google-calendar');
            return response.data.data;
        },
        staleTime: 1000 * 60 * 5, // 5 minutes
        retry: false,
    });
}

export function useGoogleCalendarEventsQuery(
    calendarStart: Ref<Dayjs | undefined>,
    calendarEnd: Ref<Dayjs | undefined>,
    connected: ComputedRef<boolean>
) {
    const dateRange = computed(() => {
        if (!calendarStart.value || !calendarEnd.value) {
            return { start: null, end: null };
        }
        return {
            start: localDateToUtc(calendarStart.value),
            end: localDateToUtc(calendarEnd.value),
        };
    });

    return useQuery<GoogleCalendarEvent[]>({
        queryKey: computed(() => ['googleCalendar', 'events', dateRange.value]),
        enabled: computed(() => connected.value && !!dateRange.value.start),
        placeholderData: (previousData) => previousData,
        queryFn: async () => {
            const response = await api.axios.get('/v1/users/me/google-calendar/events', {
                params: dateRange.value,
            });
            return response.data.data;
        },
        staleTime: 1000 * 60, // 1 minute
        retry: false,
    });
}
