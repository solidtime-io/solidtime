import { useStorage } from '@vueuse/core';

export const groupSimilarTimeEntriesSetting = useStorage<boolean>(
    'group-similar-time-entries',
    true
);
