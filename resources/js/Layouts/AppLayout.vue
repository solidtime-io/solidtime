<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import route from 'ziggy-js';
import OrganizationSwitcher from '@/Components/OrganizationSwitcher.vue';
import CurrentSidebarTimer from '@/Components/CurrentSidebarTimer.vue';
import {
    ChartBarIcon,
    ClockIcon,
    FolderIcon,
    HomeIcon,
    UserCircleIcon,
    UserGroupIcon,
    TagIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/20/solid';
import NavigationSidebarItem from '@/Components/NavigationSidebarItem.vue';
import UserSettingsIcon from '@/Components/UserSettingsIcon.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div class="flex flex-wrap bg-default-background text-muted">
        <div
            class="flex-shrink-0 h-screen fixed w-[250px] xl:w-[300px] px-2.5 xl:px-4 py-4 flex flex-col justify-between">
            <div>
                <div class="border-b border-default-background-seperator pb-2">
                    <OrganizationSwitcher></OrganizationSwitcher>
                </div>
                <div class="border-b border-default-background-seperator">
                    <CurrentSidebarTimer></CurrentSidebarTimer>
                </div>
                <nav>
                    <ul class="space-y-1">
                        <NavigationSidebarItem
                            title="Dashboard"
                            :icon="HomeIcon"
                            :href="route('dashboard')"
                            :current="
                                route().current('dashboard')
                            "></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Time"
                            :icon="ClockIcon"
                            :href="route('dashboard')"></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Reporting"
                            :icon="ChartBarIcon"
                            :href="route('dashboard')"></NavigationSidebarItem>
                    </ul>
                </nav>

                <div class="text-muted font-semibold text-sm pt-6 pb-4">
                    Manage
                </div>

                <nav>
                    <ul class="space-y-1">
                        <NavigationSidebarItem
                            title="Projects"
                            :icon="FolderIcon"
                            :href="route('dashboard')"
                            :current="
                                route().current('dashboard')
                            "></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Clients"
                            :icon="UserCircleIcon"
                            :href="route('dashboard')"></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Members"
                            :icon="UserGroupIcon"
                            :href="route('dashboard')"></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Tags"
                            :icon="TagIcon"
                            :href="route('dashboard')"></NavigationSidebarItem>
                    </ul>
                </nav>
            </div>

            <ul
                class="border-t border-default-background-seperator pt-3 flex justify-between pr-4 items-center">
                <NavigationSidebarItem
                    class="flex-1"
                    title="Settings"
                    :icon="Cog6ToothIcon"
                    :href="route('profile.show')"></NavigationSidebarItem>

                <UserSettingsIcon></UserSettingsIcon>
            </ul>
        </div>
        <div class="flex-1 ml-[250px] xl:ml-[300px]">
            <Head :title="title" />

            <Banner />

            <div
                class="min-h-screen bg-default-background border-l border-default-background-seperator">
                <!-- Page Heading -->
                <header
                    v-if="$slots.header"
                    class="bg-default-background border-b border-default-background-seperator shadow">
                    <div class="py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
