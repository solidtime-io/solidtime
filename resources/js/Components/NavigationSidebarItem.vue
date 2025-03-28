<script setup lang="ts">
import { type Component } from 'vue';
import NavigationSidebarLink from '@/Components/NavigationSidebarLink.vue';
import {
    CollapsibleContent,
    CollapsibleRoot,
    CollapsibleTrigger,
} from 'radix-vue';
import { useSessionStorage } from '@vueuse/core';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = defineProps<{
    title: string;
    icon?: Component;
    current?: boolean;
    href: string;
    subItems?: { title: string; route: string, show: boolean }[];
}>();

const open = useSessionStorage('nav-collapse-state-' + props.title, true);
</script>

<template>
    <li class="relative">
        <NavigationSidebarLink
            v-if="!subItems"
            class="py-0.5"
            :title
            :icon
            :current
            :href></NavigationSidebarLink>
        <CollapsibleRoot v-else v-model:open="open"
            ><CollapsibleTrigger class="w-full group py-0.5">
                <div
                    class="text-muted group-hover:text-text-primary group-hover:bg-menu-active group flex gap-x-2 rounded-md transition leading-6 py-1 px-2 font-medium text-sm items-center justify-between">
                    <div class="flex items-center gap-x-2">
                        <component
                            :is="icon"
                            v-if="icon"
                            :class="[
                                current
                                    ? 'text-icon-active'
                                    : 'text-icon-default group-hover:text-icon-active',
                                'transition h-5 w-5 shrink-0',
                            ]"
                            aria-hidden="true" />
                        <span>
                            {{ title }}
                        </span>
                    </div>

                    <ChevronRightIcon
                        :class="[
                            'w-5 text-text-secondary',
                            { 'transform rotate-90': open },
                        ]"></ChevronRightIcon>
                </div>
            </CollapsibleTrigger>
            <CollapsibleContent class="CollapsibleContent">
                <div class="px-3.5">
                    <ul
                        v-if="subItems"
                        class="flex min-w-0 flex-col border-l border-border-secondary px-3 w-full my-0.5">
                        <li
                            v-for="subItem in subItems"
                            :key="subItem.title"
                            class="w-full relative">
                            <NavigationSidebarLink
                                v-if="subItem.show"
                                :title="subItem.title"
                                :current="route().current(subItem.route)"
                                :href="
                                    route(subItem.route)
                                "></NavigationSidebarLink>
                        </li>
                    </ul>
                </div>
            </CollapsibleContent>
        </CollapsibleRoot>
    </li>
</template>
<style scoped>
.CollapsibleContent {
    overflow: hidden;
}
.CollapsibleContent[data-state='open'] {
    animation: slideDown 300ms ease-out;
}
.CollapsibleContent[data-state='closed'] {
    animation: slideUp 300ms ease-out;
}

@keyframes slideDown {
    from {
        height: 0;
    }
    to {
        height: var(--radix-collapsible-content-height);
    }
}

@keyframes slideUp {
    from {
        height: var(--radix-collapsible-content-height);
    }
    to {
        height: 0;
    }
}
</style>
