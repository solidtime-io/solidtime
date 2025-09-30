<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import type { Organization, User } from '@/types/models';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/Components/ui/dropdown-menu';
import {
    UserCircleIcon,
    KeyIcon,
    ArrowLeftOnRectangleIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/solid';

const page = usePage<{
    has_services_extension?: boolean;
    has_billing_extension?: boolean;
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

const logout = () => {
    router.post(route('logout'));
};

const openFeedback = () => {
    if (
        typeof window !== 'undefined' &&
        'showChatWindow' in window &&
        typeof window.showChatWindow === 'function'
    ) {
        window.showChatWindow();
    }
};
</script>
<template>
    <div class="relative">
        <DropdownMenu>
            <DropdownMenuTrigger>
                <template v-if="page.props.jetstream.managesProfilePhotos">
                    <button
                        data-testid="current_user_button"
                        class="flex text-sm border-2 outline-none border-transparent rounded-full focus:border-gray-300 transition">
                        <img
                            class="h-8 w-8 rounded-full object-cover"
                            :src="page.props.auth.user.profile_photo_url"
                            :alt="page.props.auth.user.name" />
                    </button>
                </template>
                <template v-else>
                    <span class="inline-flex rounded-md">
                        <button
                            type="button"
                            class="inline-flex outline-none items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                            {{ page.props.auth.user.name }}

                            <svg
                                class="ms-2 -me-0.5 h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </span>
                </template>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="center" class="min-w-40">
                <div class="block px-2 py-2 text-xs text-gray-400">Manage Account</div>

                <DropdownMenuItem as-child>
                    <Link
                        :href="route('profile.show')"
                        class="inline-flex items-center gap-2.5 w-full">
                        <UserCircleIcon class="w-5 h-5 text-text-secondary" />
                        <span>Profile</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="page.props.jetstream.hasApiFeatures" as-child>
                    <Link
                        :href="route('api-tokens.index')"
                        class="inline-flex items-center gap-2.5 w-full">
                        <KeyIcon class="w-5 h-5 text-text-secondary" />
                        <span>API Tokens</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="page.props.has_services_extension" as-child>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2.5 w-full"
                        @click="openFeedback">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-text-secondary" />
                        <span>Feedback</span>
                    </button>
                </DropdownMenuItem>

                <form class="w-full" @submit.prevent="logout">
                    <DropdownMenuItem as-child class="inline-flex items-center gap-2.5 w-full">
                        <button type="submit" data-testid="logout_button">
                            <ArrowLeftOnRectangleIcon class="w-5 h-5 text-text-secondary" />
                            <span>Log Out</span>
                        </button>
                    </DropdownMenuItem>
                </form>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
