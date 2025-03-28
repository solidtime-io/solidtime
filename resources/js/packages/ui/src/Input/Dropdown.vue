<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import {
    flip,
    limitShift,
    type Placement,
    shift,
    useFloating,
} from '@floating-ui/vue';
import { offset } from '@floating-ui/vue';
import { autoUpdate } from '@floating-ui/vue';
import { useId } from 'radix-vue';
import { isLastLayer, layers } from '@/packages/ui/src/utils/dismissableLayer';

const props = withDefaults(
    defineProps<{
        align?: Placement;
        closeOnContentClick?: boolean;
    }>(),
    {
        align: 'bottom-start',
        closeOnContentClick: true,
    }
);

const emit = defineEmits(['open', 'submit']);
const open = defineModel({ default: false });
const id = useId();

const closeOnEscape = (e: KeyboardEvent) => {
    if (isLastLayer(id)) {
        if (open.value && e.key === 'Escape') {
            open.value = false;
        }
        if (open.value && e.key === 'Enter') {
            emit('submit');
            if (props.closeOnContentClick) open.value = false;
        }
    }
};

watch(open, (value) => {
    if (value) {
        layers.value.push(id);
    } else {
        layers.value = layers.value.filter((layer) => layer !== id);
        reference.value
            ?.querySelector<HTMLElement>(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            )
            ?.focus();
    }
});

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

function onContentClick() {
    if (props.closeOnContentClick === true) {
        open.value = false;
    }
}

function toggleOpen() {
    open.value = !open.value;
    if (open.value === true) {
        emit('open');
    }
}

function onBackgroundClick() {
    emit('submit');
    open.value = false;
}

const reference = ref<null | HTMLElement>(null);
const floating = ref(null);
const { floatingStyles } = useFloating(reference, floating, {
    placement: props.align,
    whileElementsMounted: autoUpdate,
    middleware: [
        offset(10),
        shift({
            limiter: limitShift({
                offset: 5,
            }),
        }),
        flip({
            fallbackAxisSideDirection: 'start',
        }),
    ],
});
</script>

<template>
    <div class="min-w-0 isolate">
        <div ref="reference" class="min-w-0" @click.prevent="toggleOpen">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <Teleport to="body">
            <div
                v-show="open"
                class="fixed inset-0 z-50"
                @click.prevent="onBackgroundClick" />
            <transition
                enter-active-class="transition-opacity ease-out duration-200"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition-opacity ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95">
                <div
                    v-if="open"
                    ref="floating"
                    class="z-50"
                    :style="floatingStyles"
                    @click="onContentClick">
                    <div
                        class="rounded-lg overflow-hidden relative border border-card-border overflow-none shadow-dropdown bg-card-background">
                        <slot name="content" />
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>
