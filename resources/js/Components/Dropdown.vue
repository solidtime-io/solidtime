<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';

const props = withDefaults(
    defineProps<{
        align: string;
        width: string;
        contentClasses?: string[];
        closeOnContentClick: boolean;
    }>(),
    {
        align: 'right',
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

const emit = defineEmits(['open']);
const open = defineModel({ default: false });

const closeOnEscape = (e: KeyboardEvent) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
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

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'ltr:origin-top-left rtl:origin-top-right start-0';
    }

    if (props.align === 'right') {
        return 'ltr:origin-top-right rtl:origin-top-left end-0';
    }

    if (props.align === 'bottom-right') {
        return 'bottom-[calc(100%+15px)] ltr:origin-top-right rtl:origin-top-left end-0';
    }

    return 'origin-top';
});

function toggleOpen() {
    open.value = !open.value;
    if (open.value === true) {
        emit('open');
    }
}
</script>

<template>
    <div class="relative">
        <div @click="toggleOpen">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div v-show="open" class="fixed inset-0 z-40" @click="open = false" />

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95">
            <div
                v-show="open"
                class="absolute z-50 mt-2 rounded-md shadow-lg"
                :class="[widthClass, alignmentClasses]"
                style="display: none"
                @click="onContentClick">
                <div
                    class="rounded-lg ring-1 relative ring-black ring-opacity-5"
                    :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </transition>
    </div>
</template>
