<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import OrganizationSwitcher from '@/Components/OrganizationSwitcher.vue';
import CurrentSidebarTimer from '@/Components/CurrentSidebarTimer.vue';
import {
    Bars3Icon,
    ChartBarIcon,
    ClockIcon,
    Cog6ToothIcon,
    CreditCardIcon,
    FolderIcon,
    HomeIcon,
    TagIcon,
    UserCircleIcon,
    UserGroupIcon,
    XMarkIcon,
} from '@heroicons/vue/20/solid';
import NavigationSidebarItem from '@/Components/NavigationSidebarItem.vue';
import UserSettingsIcon from '@/Components/UserSettingsIcon.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { onMounted, ref } from 'vue';
import NotificationContainer from '@/Components/NotificationContainer.vue';
import { initializeStores, refreshStores } from '@/utils/init';
import {
    canManageBilling,
    canUpdateOrganization,
    canViewClients,
    canViewMembers,
    canViewProjects,
    canViewTags,
} from '@/utils/permissions';
import { isBillingActivated } from '@/utils/billing';
import type { User } from '@/types/models';
import { ArrowsRightLeftIcon } from '@heroicons/vue/16/solid';
import { fetchToken, isTokenValid } from '@/utils/session';
import UpdateSidebarNotification from '@/Components/UpdateSidebarNotification.vue';
import BillingBanner from '@/Components/Billing/BillingBanner.vue';

defineProps({
    title: String,
});

const showSidebarMenu = ref(false);
const isUnloading = ref(false);
onMounted(async () => {
    // make sure that the initial requests are only loaded once, this can be removed once we move away from inertia
    if (window.initialDataLoaded !== true) {
        window.initialDataLoaded = true;
        initializeStores();
    }
    window.onbeforeunload = () => {
        isUnloading.value = true;
    };
    window.onfocus = async () => {
        if (!isTokenValid()) {
            await fetchToken();
        }
        setTimeout(() => {
            // prevent store refreshing on navigation
            if (isUnloading.value === false) {
                refreshStores();
            }
        }, 100);
    };
});

const page = usePage<{
    auth: {
        user: User;
    };
}>();
</script>

<template>
    <div v-bind="$attrs" class="flex flex-wrap bg-background text-muted">
        <div
            :class="{
                '!flex bg-default-background w-full z-[9999999999]':
                    showSidebarMenu,
            }"
            class="flex-shrink-0 h-screen hidden fixed w-[230px] 2xl:w-[250px] px-2.5 2xl:px-3 py-4 lg:flex flex-col justify-between">
            <div class="flex flex-col h-full">
                <div
                    class="border-b border-default-background-separator pb-2 flex justify-between">
                    <OrganizationSwitcher class="w-full"></OrganizationSwitcher>
                    <XMarkIcon
                        @click="showSidebarMenu = false"
                        class="w-8 lg:hidden"></XMarkIcon>
                </div>
                <div class="border-b border-default-background-separator">
                    <CurrentSidebarTimer></CurrentSidebarTimer>
                </div>
                <div
                    class="overflow-y-scroll flex-1 w-[calc(100%+10px)]"
                    style="
                        scrollbar-width: thin;
                        scrollbar-color: var(--color-bg-primary) transparent;
                    ">
                    <nav class="pt-2">
                        <ul>
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
                                :href="
                                    route('reporting')
                                "></NavigationSidebarItem>
                        </ul>
                    </nav>

                    <div
                        class="text-text-tertiary text-sm font-semibold pt-5 pb-1.5">
                        Manage
                    </div>

                    <nav>
                        <ul>
                            <NavigationSidebarItem
                                v-if="canViewProjects()"
                                title="Projects"
                                :icon="FolderIcon"
                                :href="route('projects')"
                                :current="
                                    route().current('projects')
                                "></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="canViewClients()"
                                title="Clients"
                                :icon="UserCircleIcon"
                                :current="route().current('clients')"
                                :href="
                                    route('clients')
                                "></NavigationSidebarItem>
                            <NavigationSidebarItem
                                title="Members"
                                v-if="canViewMembers()"
                                :icon="UserGroupIcon"
                                :current="route().current('members')"
                                :href="
                                    route('members')
                                "></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="canViewTags()"
                                title="Tags"
                                :icon="TagIcon"
                                :current="route().current('tags')"
                                :href="route('tags')"></NavigationSidebarItem>
                        </ul>
                    </nav>
                    <div
                        v-if="canUpdateOrganization()"
                        class="text-text-tertiary text-sm font-semibold pt-5 pb-1.5">
                        Admin
                    </div>

                    <nav>
                        <ul>
                            <NavigationSidebarItem
                                v-if="
                                    canManageBilling() && isBillingActivated()
                                "
                                title="Billing"
                                :icon="CreditCardIcon"
                                href="/billing"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="canUpdateOrganization()"
                                title="Import / Export"
                                :icon="ArrowsRightLeftIcon"
                                :current="route().current('import')"
                                :href="route('import')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="canUpdateOrganization()"
                                title="Settings"
                                :icon="Cog6ToothIcon"
                                :href="
                                    route(
                                        'teams.show',
                                        page.props.auth.user.current_team.id
                                    )
                                "
                                :current="
                                    route().current(
                                        'teams.show',
                                        page.props.auth.user.current_team.id
                                    )
                                "></NavigationSidebarItem>
                        </ul>
                    </nav>
                </div>
                <div class="justify-self-end">
                    <UpdateSidebarNotification></UpdateSidebarNotification>
                    <ul
                        class="border-t border-default-background-separator pt-3 flex justify-between pr-4 items-center">
                        <NavigationSidebarItem
                            class="flex-1"
                            title="Profile Settings"
                            :icon="Cog6ToothIcon"
                            :href="
                                route('profile.show')
                            "></NavigationSidebarItem>

                        <UserSettingsIcon></UserSettingsIcon>
                    </ul>
                </div>
            </div>
        </div>
        <div class="flex-1 lg:ml-[230px] 2xl:ml-[250px] min-w-0">
            <div
                class="lg:hidden w-full px-3 py-1 border-b border-b-default-background-separator text-muted flex justify-between items-center">
                <Bars3Icon
                    @click="showSidebarMenu = !showSidebarMenu"
                    class="w-7 text-muted"></Bars3Icon>
                <OrganizationSwitcher></OrganizationSwitcher>
            </div>

            <Head :title="title" />

            <Banner />
            <BillingBanner v-if="isBillingActivated()" />

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
                <main class="pb-28">
                    <slot />
                </main>
            </div>
        </div>
    </div>
    <NotificationContainer></NotificationContainer>
</template>
