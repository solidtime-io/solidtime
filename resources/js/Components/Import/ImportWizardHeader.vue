<script setup lang="ts">
import { computed } from 'vue';
import type { ImportSource } from './importSources';
import ImportSourceLogo from './ImportSourceLogo.vue';

export interface ImportProgressSegment {
    key: string;
    title: string;
    done: boolean;
    current: boolean;
}

const props = defineProps<{
    source: ImportSource;
    // Import steps of the source, the preparation is added in front if the source has one
    segments: ImportProgressSegment[];
    // -1 is the preparation, the number of steps is the summary
    screen: number;
}>();

defineEmits<{ changeSource: [] }>();

// Sources without prerequisites open at the first step and have no preparation screen
const hasPreparation = computed(() => props.source.prerequisites.length > 0);

const allSegments = computed<ImportProgressSegment[]>(() => [
    ...(hasPreparation.value
        ? [
              {
                  key: 'preparation',
                  title: 'Preparation',
                  done: props.screen >= 0,
                  current: props.screen < 0,
              },
          ]
        : []),
    ...props.segments,
]);

const status = computed(() => {
    if (props.screen >= props.segments.length) {
        return 'Summary';
    }
    const stepNumber = props.screen + (hasPreparation.value ? 2 : 1);
    return `Step ${stepNumber} of ${allSegments.value.length}`;
});

function dotClass(segment: ImportProgressSegment): string {
    if (segment.current) {
        return 'w-5 bg-accent-400';
    }
    return segment.done ? 'w-1.5 bg-accent-400/40' : 'w-1.5 bg-card-background-active';
}
</script>

<template>
    <div
        class="@container flex items-center gap-2.5 px-4 sm:px-5 py-3.5 border-b border-card-background-separator">
        <ImportSourceLogo :source="source.id" size="small" />
        <span class="font-semibold text-text-primary truncate">{{ source.name }}</span>
        <div class="ml-1 flex flex-none items-center gap-2">
            <div class="flex items-center gap-1.5" aria-hidden="true">
                <span
                    v-for="segment in allSegments"
                    :key="segment.key"
                    :title="segment.title"
                    :class="['h-1.5 rounded-full transition-all', dotClass(segment)]"></span>
            </div>
            <!-- In narrow cards only the dots are shown -->
            <span class="hidden @sm:inline text-xs text-text-tertiary whitespace-nowrap">
                {{ status }}
            </span>
        </div>
        <span class="flex-1"></span>
        <button
            type="button"
            class="text-sm text-text-secondary hover:text-text-primary whitespace-nowrap"
            @click="$emit('changeSource')">
            Change source
        </button>
    </div>
</template>
