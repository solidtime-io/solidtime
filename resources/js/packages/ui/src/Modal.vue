<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, watch } from 'vue';
import { useId } from 'radix-vue';
import { isLastLayer, layers } from '@/packages/ui/src/utils/dismissableLayer';
import { useFocusTrap } from '@vueuse/integrations/useFocusTrap';
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'visible';
        }
    }
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};
const id = useId();

const closeOnEscape = (e: KeyboardEvent) => {
    if (isLastLayer(id)) {
        if (e.key === 'Escape' && props.show) {
            close();
        }
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = 'visible';
});

watch(
    () => props.show,
    (value) => {
        if (value) {
            layers.value.push(id);
        } else {
            layers.value = layers.value.filter((layer) => layer !== id);
        }
    }
);
const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});

const target = ref();
const { activate, deactivate } = useFocusTrap(target);
watch(
    () => props.show,
    (value) => {
        if (value) {
            nextTick(() => {
                activate();
            });
        } else {
            nextTick(() => {
                deactivate();
            });
        }
    }
);
</script>

<template>
    <teleport to="body">
        <transition leave-active-class="duration-200">
            <div
                v-show="show"
                class="fixed inset-0 overflow-y-auto px-4 py-32 sm:px-0 z-50"
                scroll-region>
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
                    <div
                        v-show="show"
                        class="fixed inset-0 transform transition-all backdrop-blur-sm"
                        @click="close">
                        <div
                            class="absolute inset-0 bg-default-background opacity-30" />
                    </div>
                </transition>

                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div
                        v-show="show"
                        role="dialog"
                        ref="target"
                        class="mb-6 bg-default-background border border-card-border rounded-lg shadow-xl transform transition-all sm:w-full sm:mx-auto"
                        :class="maxWidthClass">
                        <slot v-if="show" />
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>
