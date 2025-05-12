<script setup lang="ts">
import type { NumberFieldRootEmits, NumberFieldRootProps } from 'reka-ui'
import { cn } from '@/lib/utils'
import { NumberFieldRoot, useForwardPropsEmits } from 'reka-ui'
import { computed, type HTMLAttributes, inject, type ComputedRef } from 'vue'
import type { Organization } from '@/packages/api/src'

const props = defineProps<NumberFieldRootProps & { 
  class?: HTMLAttributes['class']
  formatOptions?: {
    maximumFractionDigits?: number
    minimumFractionDigits?: number
  }
}>()
const emits = defineEmits<NumberFieldRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, formatOptions: __, ...delegated } = props
  return delegated
})

const organization = inject<ComputedRef<Organization>>('organization')

const locale = computed(() => {
  const format = organization?.value?.number_format || 'comma-point'
  
  // space poin is not supported in reka-ui
  switch (format) {
    case 'point-comma':
      return 'de-DE' // Uses point for thousands and comma for decimal
    case 'comma-point':
      return 'en-US' // Uses comma for thousands and point for decimal
    case 'space-comma':
      return 'sv-SE' // Uses space for thousands and comma for decimal
    case 'apostrophe-point':
      return 'de-CH' // Uses apostrophe for thousands and point for decimal
    default:
      return 'en-US'
  }
})

const defaultFormatOptions = {
  maximumFractionDigits: 2
}

const formatOptions = computed(() => ({
  ...defaultFormatOptions,
  ...props.formatOptions
}))

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <NumberFieldRoot 
    v-bind="forwarded" 
    :locale="locale"
    :format-options="formatOptions"
    :class="cn('grid gap-1.5', props.class)">
    <slot />
  </NumberFieldRoot>
</template>
