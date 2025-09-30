<script setup lang="ts">
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Cog6ToothIcon,
    PlusCircleIcon,
    CheckCircleIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/solid';
import type { Organization, User } from '@/types/models';
import { isBillingActivated } from '@/utils/billing';
import { canManageBilling } from '@/utils/permissions';
import { switchOrganization } from '@/utils/useOrganization';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
} from '@/Components/ui/dropdown-menu';

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

const switchToTeam = (organization: Organization) => {
    switchOrganization(organization.id);
};
</script>

<template>
    <DropdownMenu v-if="page.props.jetstream.hasTeamFeatures">
        <DropdownMenuTrigger
            class="flex w-full text-left hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-ring cursor-pointer transition pl-2 py-1 rounded w-full items-center justify-between"
            as-child>
            <button data-testid="organization_switcher">
                <div class="flex flex-1 space-x-2 items-center w-[calc(100%-30px)]">
                    <div
                        class="rounded bg-blue-900 font-medium text-xs flex-shrink-0 text-white w-5 h-5 flex items-center justify-center">
                        {{ page.props.auth.user.current_team.name.slice(0, 1).toUpperCase() }}
                    </div>
                    <span class="text-xs flex-1 truncate font-medium">
                        {{ page.props.auth.user.current_team.name }}
                    </span>
                </div>
                <div class="w-[30px]">
                    <div class="p-1 rounded-full flex items-center w-6 h-6">
                        <ChevronDownIcon class="w-4 sm:w-full mt-[1px]"></ChevronDownIcon>
                    </div>
                </div>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="start">
            <div class="w-60">
                <DropdownMenuLabel>Manage Organization</DropdownMenuLabel>

                <DropdownMenuItem as-child>
                    <Link
                        :href="route('teams.show', page.props.auth.user.current_team.id)"
                        class="inline-flex items-center gap-2.5 w-full">
                        <Cog6ToothIcon class="w-5 h-5 text-icon-default" />
                        <span>Organization Settings</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="canManageBilling() && isBillingActivated()" as-child>
                    <Link href="/billing" class="inline-flex items-center w-full"> Billing </Link>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="page.props.jetstream.canCreateTeams" as-child>
                    <Link
                        :href="route('teams.create')"
                        class="inline-flex items-center gap-2.5 w-full">
                        <PlusCircleIcon class="w-5 h-5 text-icon-default" />
                        <span>Create new organization</span>
                    </Link>
                </DropdownMenuItem>

                <template v-if="page.props.auth.user.all_teams.length > 1">
                    <div class="border-t border-card-background-separator" />

                    <DropdownMenuLabel>Switch Organizations</DropdownMenuLabel>

                    <template v-for="team in page.props.auth.user.all_teams" :key="team.id">
                        <form @submit.prevent="switchToTeam(team)">
                            <DropdownMenuItem
                                as-child
                                class="inline-flex gap-2.5 items-center w-full">
                                <button type="submit">
                                    <CheckCircleIcon
                                        v-if="team.id == page.props.auth.user.current_team_id"
                                        class="h-5 w-5 text-green-400" />
                                    <ArrowRightIcon v-else class="h-5 w-5 text-icon-default" />

                                    <div class="w-full truncate text-left">
                                        {{ team.name }}
                                    </div>
                                </button>
                            </DropdownMenuItem>
                        </form>
                    </template>
                </template>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
