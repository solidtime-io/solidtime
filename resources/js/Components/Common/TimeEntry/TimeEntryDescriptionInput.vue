<script setup lang="ts">
const value = defineModel();
const emit = defineEmits(['changed']);

function onChange(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('changed', target.value);
}
</script>

<template>
    <div>
        <label class="input-sizer text-sm font-medium" :data-value="value">
            <input
                data-testid="time_entry_description"
                v-model="value"
                @blur="onChange"
                @keydown.enter="onChange"
                placeholder="Add a description"
                class="text-white placeholder-muted font-medium bg-transparent hover:bg-card-background rounded-lg border border-transparent hover:border-card-border" />
        </label>
    </div>
</template>

<style scoped lang="postcss">
.input-sizer {
    display: inline-grid;
    vertical-align: top;
    align-items: center;
    position: relative;

    &.stacked {
        align-items: stretch;

        &::after,
        input,
        textarea {
            grid-area: 2 / 1;
        }
    }

    &::after,
    input,
    textarea {
        width: auto;
        min-width: 1em;
        grid-area: 1 / 2;
        padding: 0.5rem 0.75rem;
        margin: 0;
        font: inherit;
        resize: none;
        background: none;
        appearance: none;
        border: none;
    }

    &::after {
        content: attr(data-value) ' ';
        visibility: hidden;
        white-space: pre-wrap;
    }
}
</style>
