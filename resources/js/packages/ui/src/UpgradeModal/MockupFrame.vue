<script setup lang="ts">
withDefaults(
    defineProps<{
        // Show the blue glow in light mode too, dark mode always has it
        glowInLightMode?: boolean;
    }>(),
    { glowInLightMode: true }
);
</script>

<template>
    <!-- Note: Shared header of the upgrade modal, all mock-ups have the same height -->
    <div
        class="relative h-[156px] overflow-hidden border-b border-border-tertiary bg-background dark:border-white/10 dark:bg-[#0b1220]">
        <!-- Note: Glow rises from behind the divider, around the pill -->
        <div
            :class="glowInLightMode ? '' : 'hidden dark:block'"
            class="pointer-events-none absolute -bottom-32 left-1/2 h-56 w-[30rem] -translate-x-1/2 rounded-full bg-accent-400/25 blur-3xl dark:bg-accent-500/[0.18]" />
        <div class="relative h-full" aria-hidden="true">
            <slot />
        </div>
    </div>
</template>
