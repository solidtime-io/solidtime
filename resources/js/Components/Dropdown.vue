<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { flip, type Placement, useFloating } from '@floating-ui/vue';
import { offset } from '@floating-ui/vue';
import { autoUpdate } from '@floating-ui/vue';

const props = withDefaults(
    defineProps<{
        align: Placement;
        width: string;
        contentClasses?: string[];
        closeOnContentClick: boolean;
    }>(),
    {
        align: 'bottom-start',
        width: '48',
        contentClasses: () => [
            'overflow-none',
            'bg-card-background',
            'border',
            'border-card-border',
        ],
        closeOnContentClick: true,
    }
);

const emit = defineEmits(['open', 'submit']);
const open = defineModel({ default: false });

const closeOnEscape = (e: KeyboardEvent) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
    if (open.value && e.key === 'Enter') {
        emit('submit');
        if (props.closeOnContentClick) open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

function onContentClick() {
    if (props.closeOnContentClick === true) {
        open.value = false;
    }
}

const widthClass = computed(() => {
    return {
        48: 'w-48',
    }[props.width.toString()];
});

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

const reference = ref(null);
const floating = ref(null);
const { floatingStyles } = useFloating(reference, floating, {
    placement: props.align,
    whileElementsMounted: autoUpdate,
    middleware: [flip(), offset(10)],
});
</script>

<template>
    <div>
        <div @click.prevent="toggleOpen" ref="reference">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click.prevent="onBackgroundClick" />
        <Teleport to="body">
            <div
                v-show="open"
                ref="floating"
                class="z-50"
                :class="[widthClass]"
                :style="floatingStyles"
                @click="onContentClick">
                <transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95">
                    <div
                        v-if="open"
                        class="rounded-lg ring-1 relative ring-black ring-opacity-5"
                        :class="contentClasses">
                        <slot name="content" />
                    </div>
                </transition>
            </div>
        </Teleport>
    </div>
</template>
