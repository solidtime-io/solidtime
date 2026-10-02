import { useQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { computed } from 'vue';

export function useTimezonesQuery() {
    const query = useQuery({
        queryKey: ['timezones'],
        queryFn: () => api.getTimezones(),
        // The list of timezones does not change while the app is open
        staleTime: Infinity,
    });

    const timezones = computed<string[]>(
        () => query.data.value?.map((timezone) => timezone.key) ?? []
    );

    return {
        ...query,
        timezones,
    };
}
