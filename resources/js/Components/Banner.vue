<script setup lang="ts">
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const ALLOWED_STYLES = ['success', 'danger', 'info', 'warning'] as const;
type BannerStyle = (typeof ALLOWED_STYLES)[number];

withDefaults(
    defineProps<{
        // Render as a self-contained rounded alert that sits inside a card
        // (e.g. the auth card on login/register) instead of a full-width page banner.
        card?: boolean;
    }>(),
    { card: false }
);

const page = usePage<{
    flash: {
        bannerText?: string;
        bannerStyle?: string;
    };
}>();

const rawStyle = page.props.flash?.bannerStyle;
const message = page.props.flash?.bannerText ?? '';
const style: BannerStyle = (ALLOWED_STYLES as readonly string[]).includes(rawStyle ?? '')
    ? (rawStyle as BannerStyle)
    : 'success';

const show = ref(true);
</script>

<template>
    <div>
        <div
            v-if="show && message"
            data-testid="banner"
            :class="
                card
                    ? 'bg-secondary border border-border-secondary rounded-lg mb-4'
                    : 'bg-secondary border-b border-border-secondary'
            ">
            <div :class="card ? 'py-2 px-3' : 'mx-auto py-1 px-3 sm:px-6 lg:px-8'">
                <div class="flex items-center justify-between flex-wrap">
                    <div
                        class="w-0 flex-1 flex min-w-0"
                        :class="card ? 'items-start' : 'items-center'">
                        <span class="flex">
                            <svg
                                v-if="style === 'success'"
                                class="h-6 w-6 text-text-secondary"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <svg
                                v-if="style === 'danger'"
                                class="h-5 w-5 text-text-primary"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>

                            <svg
                                v-if="style === 'info'"
                                class="h-6 w-6 text-text-secondary"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </span>

                        <p
                            class="ms-3 font-medium text-sm text-text-primary"
                            :class="{ truncate: !card }">
                            {{ message }}
                        </p>
                    </div>

                    <div class="shrink-0 sm:ms-3">
                        <button
                            type="button"
                            class="-me-1 flex p-2 rounded-md focus:outline-none sm:-me-2 transition hover:bg-tertiary focus:bg-tertiary"
                            aria-label="Dismiss"
                            @click.prevent="show = false">
                            <svg
                                class="h-5 w-5 text-text-primary"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
