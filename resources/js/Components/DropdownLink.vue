<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href?: string;
    as?: string;
}>();
</script>

<template>
    <div>
        <button
            v-if="as == 'button'"
            type="submit"
            v-bind="$attrs"
            class="block w-full px-4 py-2 text-start text-sm leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
            <slot />
        </button>
        <a
            v-else-if="as == 'a'"
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
            <slot />
        </a>

        <Link
            v-else
            :href="href ?? ''"
            class="block px-4 py-2 text-sm leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
            <slot />
        </Link>
    </div>
</template>
