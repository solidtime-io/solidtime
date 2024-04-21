<script setup lang="ts">
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { router, usePage } from '@inertiajs/vue3';
import type { Organization, User } from '@/types/models';
import { isBillingActivated } from '@/utils/billing';
import { initializeStores } from '@/utils/init';

const page = usePage<{
    jetstream: {
        canCreateTeams: boolean;
        hasTeamFeatures: boolean;
        managesProfilePhotos: boolean;
        hasApiFeatures: boolean;
    };
    auth: {
        user: User & {
            all_teams: Organization[];
        };
    };
}>();

const switchToTeam = (team: Organization) => {
    router.put(
        route('current-team.update'),
        {
            team_id: team.id,
        },
        {
            preserveState: false,
            onSuccess: () => {
                initializeStores();
            },
        }
    );
};
</script>

<template>
    <Dropdown
        v-if="page.props.jetstream.hasTeamFeatures"
        align="bottom"
        width="60">
        <template #trigger>
            <div
                data-testid="organization_switcher"
                class="flex hover:bg-white/10 cursor-pointer transition px-2 py-1 rounded-lg w-full items-center justify-between font-medium">
                <div
                    class="flex flex-1 space-x-2 items-center w-[calc(100%-30px)]">
                    <div
                        class="rounded sm:rounded-lg bg-blue-900 font-semibold text-xs sm:text-sm flex-shrink-0 text-white w-5 sm:w-6 h-5 sm:h-6 flex items-center justify-center">
                        {{
                            page.props.auth.user.current_team.name
                                .slice(0, 1)
                                .toUpperCase()
                        }}
                    </div>
                    <span class="text-sm flex-1 truncate font-semibold">
                        {{ page.props.auth.user.current_team.name }}
                    </span>
                </div>
                <div class="w-[30px]">
                    <button
                        class="p-1 transition hover:bg-white/10 rounded-full flex items-center w-8 h-8">
                        <ChevronDownIcon
                            class="w-5 sm:w-full mt-[1px]"></ChevronDownIcon>
                    </button>
                </div>
            </div>
        </template>

        <template #content>
            <div class="w-60">
                <!-- Organization Management -->
                <div class="block px-4 py-2 text-xs text-muted">
                    Manage Team
                </div>

                <!-- Organization Settings -->
                <DropdownLink
                    :href="
                        route(
                            'teams.show',
                            page.props.auth.user.current_team.id
                        )
                    ">
                    Organization Settings
                </DropdownLink>

                <DropdownLink
                    v-if="isBillingActivated()"
                    href="/billing">
                    Billing
                </DropdownLink>

                <DropdownLink
                    v-if="page.props.jetstream.canCreateTeams"
                    :href="route('teams.create')">
                    Create new organization
                </DropdownLink>

                <!-- Organization Switcher -->
                <template v-if="page.props.auth.user.all_teams.length > 1">
                    <div class="border-t border-card-background-separator" />

                    <div class="block px-4 py-2 text-xs text-muted">
                        Switch Organizations
                    </div>

                    <template
                        v-for="team in page.props.auth.user.all_teams"
                        :key="team.id">
                        <form @submit.prevent="switchToTeam(team)">
                            <DropdownLink as="button">
                                <div class="flex items-center">
                                    <svg
                                        v-if="
                                            team.id ==
                                            page.props.auth.user.current_team_id
                                        "
                                        class="me-2 h-5 w-5 text-green-400"
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

                                    <div>
                                        {{ team.name }}
                                    </div>
                                </div>
                            </DropdownLink>
                        </form>
                    </template>
                </template>
            </div>
        </template>
    </Dropdown>
</template>
