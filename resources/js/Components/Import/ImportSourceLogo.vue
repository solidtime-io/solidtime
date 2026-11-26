<script setup lang="ts">
import { DocumentTextIcon, PuzzlePieceIcon } from '@heroicons/vue/24/outline';
import type { ImportSourceId } from './importSources';

withDefaults(
    defineProps<{
        source: ImportSourceId;
        size?: 'small' | 'base';
    }>(),
    {
        size: 'base',
    }
);
</script>

<template>
    <span
        aria-hidden="true"
        :class="[
            'flex-none inline-flex items-center justify-center overflow-hidden',
            size === 'small' ? 'h-6 w-6 rounded-md' : 'h-9 w-9 rounded-lg',
        ]">
        <!-- Toggl Track mark (Simple Icons, CC0) -->
        <svg
            v-if="source === 'toggl'"
            viewBox="0 0 40 40"
            class="h-full w-full"
            xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" fill="#2C1338" />
            <path
                transform="translate(8 8)"
                fill="#E57CD8"
                d="M12 0a12 12 0 1 0 0 24 12 12 0 0 0 0-24zm-.883 4.322h1.766v8.757h-1.766zm-.74 2.053v1.789a4.448 4.448 0 1 0 3.247 0V6.375a6.146 6.146 0 1 1-5.669 10.552 6.145 6.145 0 0 1 2.421-10.552z" />
        </svg>
        <!-- Clockify mark (Simple Icons, CC0) -->
        <svg
            v-else-if="source === 'clockify'"
            viewBox="0 0 40 40"
            class="h-full w-full"
            xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" fill="#FFFFFF" />
            <path
                transform="translate(8 8)"
                fill="#03A9F4"
                d="M13.3512 20.4035c1.1108 0 2.169-.2217 3.139-.6157l2.6913 2.6944C17.4555 23.4458 15.4688 24 13.3512 24 6.729 24 1.3594 18.627 1.3594 12.0006 1.3594 5.3729 6.7289 0 13.3512 0c2.0964 0 4.0652.5406 5.7789 1.4865L16.4834 4.136c-.9687-.3918-2.0236-.6134-3.1322-.6134-4.6379 0-8.3976 3.779-8.3976 8.441 0 4.6609 3.7597 8.4399 8.3976 8.4399zm2.176-12.1544l5.5104-5.5139 1.5773 1.5772-5.5104 5.514zm-2.2328 5.6348c-1.0784 0-1.952-.8775-1.952-1.961 0-1.0825.8736-1.9611 1.952-1.9611 1.0784 0 1.952.8786 1.952 1.961 0 1.0836-.8736 1.9611-1.952 1.9611zm9.3462 5.6953l-1.5772 1.5783-5.5105-5.514 1.5774-1.5783z" />
        </svg>
        <!-- Harvest mark -->
        <svg
            v-else-if="source === 'harvest'"
            viewBox="0 0 70 70"
            class="h-full w-full"
            xmlns="http://www.w3.org/2000/svg">
            <rect width="70" height="70" fill="#FA5D00" />
            <path
                fill="#FFFFFF"
                d="M55.6916 55.7791C55.6916 57.2818 54.4734 58.5 52.9707 58.5H49.353C47.8503 58.5 46.6321 57.2818 46.6321 55.7791V14.2209C46.6321 12.7182 47.8503 11.5 49.353 11.5H52.9707C54.4734 11.5 55.6916 12.7182 55.6916 14.2209L55.6916 55.7791Z" />
            <path
                fill="#FFFFFF"
                d="M39.5008 27.4713C39.5008 29.4833 38.122 31.2332 36.1658 31.7039L30.4542 33.0781V17.8389C30.4542 16.3362 31.6724 15.118 33.1751 15.118H36.78C38.2827 15.118 39.5008 16.3362 39.5008 17.8389V27.4713Z" />
            <path
                fill="#FFFFFF"
                d="M23.3553 47.3555C23.3552 48.8581 22.1371 50.0761 20.6345 50.0761H17.0296C15.5268 50.0761 14.3086 48.8578 14.3087 47.355L14.3108 25.2504C14.311 23.7478 15.5291 22.5298 17.0317 22.5298H20.6366C22.1394 22.5298 23.3576 23.7481 23.3574 25.2509L23.3553 47.3555Z" />
            <path
                fill="#FFFFFF"
                d="M39.5037 53.165C39.5041 54.668 38.2859 55.8867 36.7829 55.8867H33.1779C31.6755 55.8867 30.4575 54.669 30.457 53.1667L30.4537 42.7934C30.4531 40.7809 31.832 39.0302 33.7887 38.5594L39.4993 37.1854L39.5037 53.165Z" />
        </svg>
        <!-- solidtime mark, light on dark in both themes like the other brand marks -->
        <span
            v-else-if="source === 'solidtime'"
            class="h-full w-full bg-[oklch(0.25_0_0)] text-[oklch(0.97_0_0)] flex items-center justify-center">
            <svg viewBox="0 0 700 700" class="h-3/4 w-3/4" xmlns="http://www.w3.org/2000/svg">
                <g transform="translate(0 700) scale(0.1 -0.1)" fill="currentColor">
                    <path
                        d="M3578 5835c-1-1-61-5-133-9-135-7-196-12-255-22-19-3-51-8-70-10-19-3-51-9-70-14-19-5-53-12-74-16-47-7-203-59-296-99-444-188-719-448-855-811-25-67-63-233-70-309-6-56-6-315-1-345 2-14 7-50 11-80 22-176 93-359 192-498 213-299 588-491 1338-687 215-56 395-129 462-188 41-36 73-95 75-140 5-87-32-146-117-186-114-53-385-60-627-15-97 17-262 72-367 121-117 54-362 216-389 257-4 6-21 24-38 39l-32 29-225-229c-124-125-318-322-431-438l-205-210 57-55c292-278 615-463 1078-618 111-38 254-78 314-88 14-3 41-9 60-14 34-8 137-26 210-35 180-24 617-24 805 0 712 92 1222 453 1390 981 66 209 78 509 30 785-11 64-51 172-97 264-137 275-327 450-685 629-145 73-485 191-638 222-93 18-347 101-433 140-135 62-187 122-185 217 1 80 73 152 180 183 59 17 295 24 373 10 132-22 331-86 435-138 73-38 202-133 286-212l73-68 434 436c389 391 433 438 421 453-38 47-57 69-85 97-252 253-524 409-929 534-254 79-441 113-710 127-60 4-131 8-157 10-25 2-48 2-50 0z" />
                </g>
            </svg>
        </span>
        <span
            v-else
            class="h-full w-full bg-card-background-active text-text-secondary flex items-center justify-center">
            <DocumentTextIcon v-if="source === 'generic'" class="h-1/2 w-1/2" />
            <PuzzlePieceIcon v-else class="h-1/2 w-1/2" />
        </span>
    </span>
</template>
