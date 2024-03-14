<script setup lang="ts">
import dayjs from 'dayjs';

defineProps<{
    date: string;
    duration: number;
}>();
import relativeTime from 'dayjs/plugin/relativeTime';
import isToday from 'dayjs/plugin/isToday';
import isYesterday from 'dayjs/plugin/isYesterday';
import { formatHumanReadableDuration } from '@/utils/time';

dayjs.extend(relativeTime);
dayjs.extend(isToday);
dayjs.extend(isYesterday);
function dayFormat(date: string) {
    if (dayjs(date).isToday()) {
        return 'Today';
    } else if (dayjs(date).isYesterday()) {
        return 'Yesterday';
    }
    return dayjs(date).fromNow();
}
</script>

<template>
    <div class="px-4 py-1.5 grid grid-cols-3">
        <div class="flex items-center">
            <p class="font-semibold text-sm text-white">
                {{ dayFormat(date) }}
            </p>
        </div>
        <div class="flex items-center justify-center">
            <svg
                class="w-20 opacity-70 transition hover:opacity-100"
                viewBox="0 0 42 10"
                fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <rect
                    y="9.28572"
                    width="4.18367"
                    height="0.714286"
                    rx="0.357143"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
                <rect
                    x="5.37939"
                    y="7.85715"
                    width="4.18367"
                    height="2.14286"
                    rx="0.714286"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
                <rect
                    x="10.7578"
                    y="4.28572"
                    width="4.18367"
                    height="5.71429"
                    rx="0.714286"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
                <rect
                    x="16.1372"
                    width="4.18367"
                    height="10"
                    rx="0.714286"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
                <rect
                    width="4.18367"
                    height="6.42857"
                    rx="0.714286"
                    transform="matrix(1 0 0 -1 21.5161 10)"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
                <rect
                    width="4.18367"
                    height="5"
                    rx="0.714286"
                    transform="matrix(1 0 0 -1 26.8955 10)"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
                <rect
                    width="4.18367"
                    height="0.714286"
                    rx="0.357143"
                    transform="matrix(1 0 0 -1 32.2739 10)"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
                <rect
                    width="4.18367"
                    height="0.714286"
                    rx="0.357143"
                    transform="matrix(1 0 0 -1 37.6533 10)"
                    fill="#B0D7FF"
                    fill-opacity="0.9" />
            </svg>
        </div>
        <div class="flex items-center justify-center text-muted font-semibold">
            {{ formatHumanReadableDuration(duration) }}
        </div>
    </div>
</template>
