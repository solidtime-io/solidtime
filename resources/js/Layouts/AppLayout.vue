<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import OrganizationSwitcher from '@/Components/OrganizationSwitcher.vue';
import CurrentSidebarTimer from '@/Components/CurrentSidebarTimer.vue';
import {
    CalendarIcon,
    ChartBarIcon,
    ClockIcon,
    Cog6ToothIcon,
    CreditCardIcon,
    FolderIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    TagIcon,
    UserCircleIcon,
    UserGroupIcon,
    XMarkIcon,
    DocumentTextIcon,
} from '@heroicons/vue/20/solid';
import { PanelLeft } from 'lucide-vue-next';
import NavigationSidebarItem from '@/Components/NavigationSidebarItem.vue';
import UserSettingsIcon from '@/Components/UserSettingsIcon.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { nextTick, onMounted, provide, ref } from 'vue';
import NotificationContainer from '@/Components/NotificationContainer.vue';
import { initializeStores } from '@/utils/init';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import {
    canManageBilling,
    canUpdateOrganization,
    canViewClients,
    canViewInvoices,
    canViewMembers,
    canViewProjects,
    canViewReport,
    canViewTags,
} from '@/utils/permissions';
import { isBillingActivated, isInvoicingActivated } from '@/utils/billing';
import type { User } from '@/types/models';
import { ArrowsRightLeftIcon } from '@heroicons/vue/16/solid';
import { fetchToken, isTokenValid } from '@/utils/session';
import UpdateSidebarNotification from '@/Components/UpdateSidebarNotification.vue';
import BillingBanner from '@/Components/Billing/BillingBanner.vue';
import UserTimezoneMismatchModal from '@/Components/Common/User/UserTimezoneMismatchModal.vue';
import { useTheme } from '@/utils/theme';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import { twMerge } from 'tailwind-merge';
import { Button } from '@/packages/ui/src/Buttons';
import { openFeedback } from '@/utils/feedback';
import { CommandPaletteProvider } from '@/Components/CommandPalette';
import { useCommandPalette } from '@/utils/useCommandPalette';

const { openPalette } = useCommandPalette();

defineProps({
    title: String,
    mainClass: String,
});

const showSidebarMenu = ref(false);
const sidebarVisible = ref(false);

function openSidebar() {
    showSidebarMenu.value = true;
    nextTick(() => {
        requestAnimationFrame(() => {
            sidebarVisible.value = true;
        });
    });
}

function closeSidebar() {
    sidebarVisible.value = false;
    setTimeout(() => {
        showSidebarMenu.value = false;
    }, 200);
}

const isUnloading = ref(false);

const { organization, isLoading: isOrganizationLoading } = useOrganizationQuery(
    getCurrentOrganizationId()!
);

provide('organization', organization);

onMounted(async () => {
    useTheme();
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
            // TanStack Query automatically refetches on window focus
            // Only refresh non-migrated stores
            if (isUnloading.value === false) {
                useCurrentTimeEntryStore().fetchCurrentTimeEntry();
            }
        }, 100);
    };
});
const page = usePage<{
    has_services_extension?: boolean;
    auth: {
        user: User;
    };
}>();
</script>

<template>
    <div v-bind="$attrs" class="flex flex-wrap bg-background text-text-secondary">
        <!-- Mobile sidebar overlay -->
        <Teleport to="body">
            <div v-if="showSidebarMenu" class="fixed inset-0 z-40 lg:hidden" @click="closeSidebar">
                <div
                    class="absolute inset-0 bg-default-background transition-opacity duration-200"
                    :class="sidebarVisible ? 'opacity-50' : 'opacity-0'" />
            </div>
        </Teleport>

        <div
            :class="[
                sidebarVisible
                    ? 'max-lg:translate-x-0 max-lg:shadow-xl'
                    : 'max-lg:-translate-x-full',
            ]"
            class="flex-shrink-0 h-screen fixed w-[280px] px-2.5 py-4 hidden lg:flex flex-col justify-between bg-background border-r border-default-background-separator max-lg:z-50 max-lg:transition-transform max-lg:duration-200 max-lg:ease-in-out lg:w-[230px] 2xl:w-[250px] 2xl:px-3 lg:border-r-0"
            :style="showSidebarMenu ? { display: 'flex' } : undefined">
            <div class="flex flex-col h-full">
                <div
                    class="border-b border-default-background-separator pb-2 flex items-center gap-1">
                    <div class="flex-1 min-w-0 overflow-hidden">
                        <OrganizationSwitcher></OrganizationSwitcher>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 flex-shrink-0"
                        data-testid="command_palette_button"
                        @click="openPalette">
                        <MagnifyingGlassIcon class="h-4 w-4 text-icon-default" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 flex-shrink-0 lg:hidden"
                        @click="closeSidebar">
                        <XMarkIcon class="h-4 w-4 text-icon-default" />
                    </Button>
                </div>
                <div class="border-b border-default-background-separator">
                    <CurrentSidebarTimer></CurrentSidebarTimer>
                </div>
                <div
                    class="overflow-y-scroll flex-1 w-full"
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
                                :current="route().current('dashboard')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                title="Time"
                                :icon="ClockIcon"
                                :current="route().current('time')"
                                :href="route('time')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                title="Calendar"
                                :icon="CalendarIcon"
                                :current="route().current('calendar')"
                                :href="route('calendar')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                title="Reporting"
                                :icon="ChartBarIcon"
                                :sub-items="[
                                    {
                                        title: 'Overview',
                                        route: 'reporting',
                                        show: true,
                                    },
                                    {
                                        title: 'Detailed',
                                        route: 'reporting.detailed',
                                        show: true,
                                    },
                                    {
                                        title: 'Shared',
                                        route: 'reporting.shared',
                                        show: canViewReport(),
                                    },
                                ]"
                                :current="
                                    route().current('reporting') ||
                                    route().current('reporting.detailed') ||
                                    route().current('reporting.shared')
                                "
                                :href="route('reporting')">
                            </NavigationSidebarItem>
                        </ul>
                    </nav>

                    <div class="text-text-tertiary text-xs font-semibold pt-5 pb-1.5">Manage</div>

                    <nav>
                        <ul>
                            <NavigationSidebarItem
                                v-if="canViewProjects()"
                                title="Projects"
                                :icon="FolderIcon"
                                :href="route('projects')"
                                :current="route().current('projects')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="canViewClients()"
                                title="Clients"
                                :icon="UserCircleIcon"
                                :current="route().current('clients')"
                                :href="route('clients')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="canViewMembers()"
                                title="Members"
                                :icon="UserGroupIcon"
                                :current="route().current('members')"
                                :href="route('members')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="canViewTags()"
                                title="Tags"
                                :icon="TagIcon"
                                :current="route().current('tags')"
                                :href="route('tags')"></NavigationSidebarItem>
                            <NavigationSidebarItem
                                v-if="isInvoicingActivated() && canViewInvoices()"
                                title="Invoices"
                                :icon="DocumentTextIcon"
                                :current="route().current('invoices')"
                                href="/invoices"></NavigationSidebarItem>
                        </ul>
                    </nav>
                    <div
                        v-if="canUpdateOrganization()"
                        class="text-text-tertiary text-xs font-semibold pt-5 pb-1.5">
                        Admin
                    </div>

                    <nav>
                        <ul>
                            <NavigationSidebarItem
                                v-if="canManageBilling() && isBillingActivated()"
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
                                :href="route('teams.show', page.props.auth.user.current_team.id)"
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
                        class="border-t border-default-background-separator pt-3 gap-1 pr-2 flex justify-between items-center">
                        <UserSettingsIcon></UserSettingsIcon>

                        <NavigationSidebarItem
                            class="flex-1"
                            title="Profile Settings"
                            :icon="Cog6ToothIcon"
                            :href="route('profile.show')"></NavigationSidebarItem>

                        <Button
                            v-if="page.props.has_services_extension"
                            variant="outline"
                            size="xs"
                            class="rounded-full ml-2 flex h-6 w-6 items-center text-xs text-icon-default justify-center"
                            @click="openFeedback">
                            ?
                        </Button>
                    </ul>
                </div>
            </div>
        </div>
        <div class="flex-1 lg:ml-[230px] 2xl:ml-[250px] min-w-0">
            <div
                class="h-screen overflow-y-auto flex flex-col bg-default-background border-l border-default-background-separator">
                <div
                    class="lg:hidden w-full px-3 py-1 border-b border-b-default-background-separator text-text-secondary flex justify-between items-center">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 shrink-0"
                        @click="openSidebar">
                        <PanelLeft class="h-4 w-4 text-icon-default" />
                    </Button>
                    <div class="flex items-center gap-1">
                        <OrganizationSwitcher></OrganizationSwitcher>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 shrink-0"
                            data-testid="command_palette_button_mobile"
                            @click="openPalette">
                            <MagnifyingGlassIcon class="h-4 w-4 text-icon-default" />
                        </Button>
                    </div>
                </div>

                <Head :title="title" />

                <!-- Page Heading -->
                <Banner />
                <BillingBanner v-if="isBillingActivated()" />

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
                <main :class="twMerge('pb-28 relative flex-1', mainClass)">
                    <div
                        v-if="isOrganizationLoading"
                        class="flex items-center justify-center h-screen">
                        <LoadingSpinner />
                    </div>
                    <slot v-else />
                </main>
            </div>
        </div>
    </div>
    <NotificationContainer></NotificationContainer>
    <UserTimezoneMismatchModal></UserTimezoneMismatchModal>
    <CommandPaletteProvider></CommandPaletteProvider>
</template>
