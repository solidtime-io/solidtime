<script setup lang="ts">
import { cn } from '@/lib/utils'
import {
  DialogContent,
  type DialogContentEmits,
  type DialogContentProps,
  DialogOverlay,
  DialogPortal,
  useForwardPropsEmits,
} from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="fixed inset-0 z-50 backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
    >
    <div
                            class="absolute inset-0 bg-default-background opacity-30" />
</DialogOverlay>
    <div
      :class="
        cn(
          'fixed top-0 left-0 z-50 pointer-events-none w-screen h-screen flex items-start pt-6 md:pt-20 xl:pt-32 justify-center overflow-auto',
        )"
    >
    <DialogContent
    v-bind="forwarded"
:class="cn(
          'bg-default-background grid w-full max-w-lg border border-border-tertiary shadow-lg duration-200 sm:rounded-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
          props.class,
        )"
      >
        <slot />
      </DialogContent>
    </div>
  </DialogPortal>
</template>
