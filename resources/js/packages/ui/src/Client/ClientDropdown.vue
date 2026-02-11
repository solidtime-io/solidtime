<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import type { CreateClientBody, Client } from '@/packages/api/src';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';
import { UseFocusTrap } from '@vueuse/integrations/useFocusTrap/component';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { Check, Plus } from 'lucide-vue-next';

const model = defineModel<string | null>({
    default: null,
});

const props = defineProps<{
    clients: Client[];
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
}>();

const searchInput = ref<HTMLElement | null>(null);
const open = ref(false);
const searchValue = ref('');

function isClientSelected(id: string) {
    return model.value === id;
}

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            // @ts-expect-error We need to access the actual HTML Element to focus as radix-vue does not support any other way right now
            searchInput.value?.$el?.focus();
        });
    }
});

const filteredClients = computed(() => {
    return props.clients.filter((client) => {
        return client.name.toLowerCase().includes(searchValue.value?.toLowerCase()?.trim() || '');
    });
});

async function addClientIfNoneExists() {
    if (searchValue.value.length > 0 && filteredClients.value.length === 0) {
        const newClient = await props.createClient({
            name: searchValue.value,
        });
        if (newClient) {
            model.value = newClient.id;
            searchValue.value = '';
            open.value = false;
        }
    }
}

const currentClient = computed(() => {
    return (
        props.clients.find((client) => client.id === model.value) ?? {
            id: null,
            name: 'No Client',
        }
    );
});

const emit = defineEmits(['update:modelValue', 'changed']);

function updateValue(client: { id: string | null; name: string }) {
    model.value = client.id;
    emit('changed');
}
</script>

<template>
    <Dropdown v-model="open" align="start">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
        <template #content>
            <UseFocusTrap v-if="open" :options="{ immediate: true, allowOutsideClick: true }">
                <ComboboxRoot
                    v-model:search-term="searchValue"
                    v-model:open="open"
                    :model-value="currentClient"
                    class="relative"
                    @update:model-value="updateValue">
                    <ComboboxAnchor>
                        <ComboboxInput
                            ref="searchInput"
                            class="bg-transparent border-0 placeholder-muted-foreground text-sm text-popover-foreground py-2 px-3 focus:ring-0 border-b border-popover-border focus:border-popover-border w-full"
                            placeholder="Search for a client..." />
                    </ComboboxAnchor>
                    <ComboboxContent>
                        <ComboboxViewport
                            class="w-[--reka-popper-anchor-width] max-h-60 overflow-y-scroll p-1">
                            <ComboboxItem
                                :value="{ id: null, name: 'No Client' }"
                                class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground">
                                <span>No Client</span>
                                <span
                                    v-if="model === null"
                                    class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
                                    <Check class="h-4 w-4" />
                                </span>
                            </ComboboxItem>
                            <ComboboxItem
                                v-for="client in filteredClients"
                                :key="client.id"
                                :value="client"
                                class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                                :data-client-id="client.id">
                                <span>{{ client.name }}</span>
                                <span
                                    v-if="isClientSelected(client.id)"
                                    class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
                                    <Check class="h-4 w-4" />
                                </span>
                            </ComboboxItem>
                            <div
                                v-if="searchValue.length > 0 && filteredClients.length === 0"
                                class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm cursor-pointer hover:bg-accent hover:text-accent-foreground"
                                @click="addClientIfNoneExists">
                                <Plus class="h-4 w-4 shrink-0" />
                                <span>Add "{{ searchValue }}" as a new Client</span>
                            </div>
                        </ComboboxViewport>
                    </ComboboxContent>
                </ComboboxRoot>
            </UseFocusTrap>
        </template>
    </Dropdown>
</template>

<style scoped></style>
