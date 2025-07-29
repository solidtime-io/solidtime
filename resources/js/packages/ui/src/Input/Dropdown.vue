<script setup lang="ts">
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { watch } from 'vue';

const props = withDefaults(
    defineProps<{
        align?: 'center' | 'end' | 'start';
        closeOnContentClick?: boolean;
        autoFocus?: boolean;
    }>(),
    {
        align: 'start',
        closeOnContentClick: true,
        autoFocus: true,
    }
);

const emit = defineEmits(['open', 'submit']);
const open = defineModel({ default: false });

function handleAutofocus(event: Event) {
    if (props.autoFocus === false) {
        event.preventDefault();
    }
}

function onContentClick() {
    if (props.closeOnContentClick === true) {
        open.value = false;
    }
}

function onOpenChange(value: boolean) {
    open.value = value;
    if (value === true) {
        emit('open');
    }
}

watch(open, (value) => {
    if (value === false) {
        emit('submit');
    }
});
</script>

<template>
    <div class="min-w-0 isolate">
        <Popover v-model:open="open" @update:open="onOpenChange">
            <PopoverTrigger as-child>
                <slot class="min-w-0 flex items-center" name="trigger" />
            </PopoverTrigger>
            <PopoverContent
                :align="align"
                class="rounded-lg overflow-hidden relative border border-card-border overflow-none shadow-dropdown bg-card-background"
                @open-auto-focus="handleAutofocus"
                @click="onContentClick">
                <slot name="content" />
            </PopoverContent>
        </Popover>
    </div>
</template>
