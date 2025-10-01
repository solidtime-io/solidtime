<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import type { Organization, User } from '@/types/models';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
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
            <DropdownMenuTrigger
                class="flex text-sm border-2 outline-none border-transparent rounded-full focus-visible:ring-2 focus-visible:ring-ring transition"
                as-child>
                <button data-testid="current_user_button">
                    <img
                        class="h-8 w-8 rounded-full object-cover"
                        :src="page.props.auth.user.profile_photo_url"
                        :alt="page.props.auth.user.name" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="center" class="max-w-48">
                <DropdownMenuLabel>Manage Account</DropdownMenuLabel>

                <DropdownMenuItem as-child>
                    <Link
                        :href="route('profile.show')"
                        class="inline-flex items-center gap-2.5 w-full">
                        <UserCircleIcon class="w-5 h-5 text-icon-default" />
                        <span>Profile Settings</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="page.props.jetstream.hasApiFeatures" as-child>
                    <Link
                        :href="route('api-tokens.index')"
                        class="inline-flex items-center gap-2.5 w-full">
                        <KeyIcon class="w-5 h-5 text-icon-default" />
                        <span>API Tokens</span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem v-if="page.props.has_services_extension" as-child>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2.5 w-full"
                        @click="openFeedback">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-icon-default" />
                        <span>Feedback</span>
                    </button>
                </DropdownMenuItem>

                <form class="w-full" @submit.prevent="logout">
                    <DropdownMenuItem as-child class="inline-flex items-center gap-2.5 w-full">
                        <button type="submit" data-testid="logout_button">
                            <ArrowLeftOnRectangleIcon class="w-5 h-5 text-icon-default" />
                            <span>Log Out</span>
                        </button>
                    </DropdownMenuItem>
                </form>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
