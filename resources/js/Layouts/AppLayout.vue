<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import OrganizationSwitcher from '@/Components/OrganizationSwitcher.vue';
import CurrentSidebarTimer from '@/Components/CurrentSidebarTimer.vue';
import {
    ChartBarIcon,
    ClockIcon,
    Cog6ToothIcon,
    FolderIcon,
    HomeIcon,
    TagIcon,
    UserCircleIcon,
    UserGroupIcon,
    Bars3Icon,
    XMarkIcon,
} from '@heroicons/vue/20/solid';
import NavigationSidebarItem from '@/Components/NavigationSidebarItem.vue';
import UserSettingsIcon from '@/Components/UserSettingsIcon.vue';
import MainContainer from '@/Pages/MainContainer.vue';
import { onMounted, ref } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import { useTagsStore } from '@/utils/useTags';
import { useTasksStore } from '@/utils/useTasks';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useClientsStore } from '@/utils/useClients';
import { useMembersStore } from '@/utils/useMembers';
import NotificationContainer from '@/Components/NotificationContainer.vue';

defineProps({
    title: String,
});

const showSidebarMenu = ref(false);

onMounted(async () => {
    useProjectsStore().fetchProjects();
    useTasksStore().fetchTasks();
    useTagsStore().fetchTags();
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
    useClientsStore().fetchClients();
    useMembersStore().fetchMembers();
});
</script>

<template>
    <div
        v-bind="$attrs"
        class="flex flex-wrap bg-default-background text-muted">
        <div
            :class="{
                '!flex bg-default-background w-full z-50': showSidebarMenu,
            }"
            class="flex-shrink-0 h-screen hidden fixed w-[230px] 2xl:w-[270px] px-2.5 2xl:px-4 py-4 sm:flex flex-col justify-between">
            <div>
                <div
                    class="border-b border-default-background-separator pb-2 flex justify-between">
                    <OrganizationSwitcher></OrganizationSwitcher>
                    <XMarkIcon
                        @click="showSidebarMenu = false"
                        class="w-8"></XMarkIcon>
                </div>
                <div class="border-b border-default-background-separator">
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
                            :current="route().current('time')"
                            :href="route('time')"></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Reporting"
                            :icon="ChartBarIcon"
                            :current="route().current('reporting')"
                            :href="route('reporting')"></NavigationSidebarItem>
                    </ul>
                </nav>

                <div class="text-muted text-sm font-bold pt-6 pb-4">Manage</div>

                <nav>
                    <ul class="space-y-1">
                        <NavigationSidebarItem
                            title="Projects"
                            :icon="FolderIcon"
                            :href="route('projects')"
                            :current="
                                route().current('projects')
                            "></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Clients"
                            :icon="UserCircleIcon"
                            :current="route().current('clients')"
                            :href="route('clients')"></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Members"
                            :icon="UserGroupIcon"
                            :current="route().current('members')"
                            :href="route('members')"></NavigationSidebarItem>
                        <NavigationSidebarItem
                            title="Tags"
                            :icon="TagIcon"
                            :current="route().current('tags')"
                            :href="route('tags')"></NavigationSidebarItem>
                    </ul>
                </nav>
            </div>

            <ul
                class="border-t border-default-background-separator pt-3 flex justify-between pr-4 items-center">
                <NavigationSidebarItem
                    class="flex-1"
                    title="Settings"
                    :icon="Cog6ToothIcon"
                    :href="route('profile.show')"></NavigationSidebarItem>

                <UserSettingsIcon></UserSettingsIcon>
            </ul>
        </div>
        <div class="flex-1 sm:ml-[230px] 2xl:ml-[270px]">
            <div
                class="sm:hidden w-full px-3 py-3 border-b border-b-default-background-separator text-muted flex justify-between items-center">
                <Bars3Icon
                    @click="showSidebarMenu = !showSidebarMenu"
                    class="w-8 text-muted"></Bars3Icon>
                <OrganizationSwitcher></OrganizationSwitcher>
            </div>

            <Head :title="title" />

            <Banner />

            <div
                class="min-h-screen bg-default-background border-l border-default-background-separator">
                <!-- Page Heading -->
                <header
                    v-if="$slots.header"
                    class="bg-default-background border-b border-default-background-separator shadow">
                    <div class="pt-8 pb-3">
                        <MainContainer>
                            <slot name="header" />
                        </MainContainer>
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
    <NotificationContainer></NotificationContainer>
</template>
