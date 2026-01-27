<script setup lang="ts">
import { computed, watch } from 'vue';
import { DialogRoot, DialogPortal, DialogOverlay, DialogContent } from 'reka-ui';
import {
    Command as CommandRoot,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
    CommandShortcut,
} from '../command';
import { cn } from '../utils/cn';
import type {
    CommandPaletteCommand,
    CommandPaletteGroup,
    EntitySearchResult,
} from './CommandPaletteTypes';

const open = defineModel<boolean>('open', { required: true });
const searchTerm = defineModel<string>('searchTerm', { default: '' });

const props = withDefaults(
    defineProps<{
        groups: CommandPaletteGroup[];
        entityResults?: EntitySearchResult[];
        placeholder?: string;
    }>(),
    {
        entityResults: () => [],
        placeholder: 'Type a command or search...',
    }
);

const emit = defineEmits<{
    select: [command: CommandPaletteCommand | EntitySearchResult];
}>();

// Non-empty groups for rendering
const nonEmptyGroups = computed(() => props.groups.filter((g) => g.commands.length > 0));

const hasEntityResults = computed(() => (props.entityResults?.length ?? 0) > 0);

const hasAnyGroups = computed(() => nonEmptyGroups.value.length > 0);

// Handle command selection
async function handleSelect(cmd: CommandPaletteCommand | EntitySearchResult) {
    emit('select', cmd);
    await cmd.action();
}

// Reset search when dialog closes
watch(open, (isOpen) => {
    if (!isOpen) {
        searchTerm.value = '';
    }
});
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
                <div class="absolute inset-0 bg-default-background opacity-30" />
            </DialogOverlay>
            <div
                :class="
                    cn(
                        'fixed top-0 left-0 z-50 pointer-events-none w-screen h-screen flex items-start pt-6 md:pt-20 xl:pt-32 justify-center overflow-auto'
                    )
                ">
                <DialogContent
                    class="pointer-events-auto bg-default-background w-full max-w-lg border border-border-tertiary shadow-lg sm:rounded-lg outline-none overflow-hidden p-0 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95">
                    <CommandRoot
                        v-model:search-term="searchTerm"
                        class="[&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:font-medium [&_[cmdk-group-heading]]:text-muted-foreground [&_[cmdk-group]:not([hidden])_~[cmdk-group]]:pt-0 [&_[cmdk-group]]:px-2 [&_[cmdk-input-wrapper]_svg]:h-5 [&_[cmdk-input-wrapper]_svg]:w-5 [&_[cmdk-input]]:h-12 [&_[cmdk-item]]:px-2 [&_[cmdk-item]]:py-3 [&_[cmdk-item]_svg]:h-5 [&_[cmdk-item]_svg]:w-5">
                        <CommandInput :placeholder="placeholder" />
                        <CommandList>
                            <!-- Empty state -->
                            <div
                                v-if="searchTerm.length > 0 && !hasEntityResults && !hasAnyGroups"
                                class="py-6 text-center text-sm text-muted-foreground">
                                No results found.
                            </div>

                            <!-- Command Groups -->
                            <template v-for="(group, index) in nonEmptyGroups" :key="group.id">
                                <CommandSeparator v-if="index > 0" />
                                <CommandGroup :heading="group.heading">
                                    <CommandItem
                                        v-for="cmd in group.commands"
                                        :key="cmd.id"
                                        :value="cmd.id"
                                        class="cursor-pointer"
                                        @select="handleSelect(cmd)">
                                        <component :is="cmd.icon" v-if="cmd.icon" />
                                        <span>{{ cmd.label }}</span>
                                        <span class="sr-only" aria-hidden="true">{{
                                            cmd.keywords.join(' ')
                                        }}</span>
                                        <CommandShortcut v-if="cmd.shortcut">
                                            {{ cmd.shortcut }}
                                        </CommandShortcut>
                                    </CommandItem>
                                </CommandGroup>
                            </template>

                            <!-- Entity Search Results -->
                            <template v-if="hasEntityResults">
                                <CommandSeparator v-if="hasAnyGroups" />
                                <CommandGroup heading="Search Results">
                                    <CommandItem
                                        v-for="cmd in entityResults"
                                        :key="cmd.id"
                                        :value="cmd.id"
                                        class="cursor-pointer"
                                        @select="handleSelect(cmd)">
                                        <component :is="cmd.icon" v-if="cmd.icon" />
                                        <span class="flex-1">{{ cmd.label }}</span>
                                        <span class="sr-only" aria-hidden="true">{{
                                            cmd.keywords.join(' ')
                                        }}</span>
                                        <span
                                            v-if="cmd.badgeClass"
                                            class="ml-2 rounded px-1.5 py-0.5 text-xs font-medium"
                                            :class="cmd.badgeClass">
                                            {{ cmd.entityType }}
                                        </span>
                                    </CommandItem>
                                </CommandGroup>
                            </template>
                        </CommandList>
                    </CommandRoot>
                </DialogContent>
            </div>
        </DialogPortal>
    </DialogRoot>
</template>
