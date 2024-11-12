import { ref } from 'vue';

export const layers = ref<string[]>([]);

export function isLastLayer(id: string) {
    return layers.value[layers.value.length - 1] === id;
}
