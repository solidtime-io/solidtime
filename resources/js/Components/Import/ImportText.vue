<script setup lang="ts">
// Text with parts wrapped in ** shown bold
defineProps<{
    text: string;
}>();
</script>

<template>
    <template v-for="(part, index) in text.split('**')" :key="index"
        ><strong v-if="index % 2 === 1" class="font-semibold">{{ part }}</strong
        ><template v-else>{{ part }}</template></template
    >
</template>
