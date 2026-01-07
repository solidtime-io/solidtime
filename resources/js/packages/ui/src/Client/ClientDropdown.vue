<script setup lang="ts">
import { PlusCircleIcon } from '@heroicons/vue/20/solid';
import { computed, nextTick, ref, watch } from 'vue';
import ClientDropdownItem from '@/packages/ui/src/Client/ClientDropdownItem.vue';
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
    <Dropdown v-model="open" align="start" width="60">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
        <template #content>
            <UseFocusTrap v-if="open" :options="{ immediate: true, allowOutsideClick: true }">
                <ComboboxRoot
                    v-model:search-term="searchValue"
                    :open="open"
                    :model-value="currentClient"
                    class="relative"
                    @update:model-value="updateValue">
                    <ComboboxAnchor>
                        <ComboboxInput
                            ref="searchInput"
                            class="bg-card-background border-0 placeholder-text-tertiary text-sm text-text-primary py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                            placeholder="Search for a client..." />
                    </ComboboxAnchor>
                    <ComboboxContent>
                        <ComboboxViewport class="w-60 max-h-60 overflow-y-scroll">
                            <ComboboxItem
                                :value="{ id: null, name: 'No Client' }"
                                class="data-[highlighted]:bg-card-background-active">
                                <ClientDropdownItem :selected="model === null" name="No Client" />
                            </ComboboxItem>
                            <ComboboxItem
                                v-for="client in filteredClients"
                                :key="client.id"
                                :value="client"
                                class="data-[highlighted]:bg-card-background-active"
                                :data-client-id="client.id">
                                <ClientDropdownItem
                                    :selected="isClientSelected(client.id)"
                                    :name="client.name" />
                            </ComboboxItem>
                            <div
                                v-if="searchValue.length > 0 && filteredClients.length === 0"
                                class="bg-card-background-active">
                                <div
                                    class="flex space-x-3 items-center px-4 py-3 text-xs text-text-primary font-medium border-t rounded-b-lg border-card-background-separator"
                                    @click="addClientIfNoneExists">
                                    <PlusCircleIcon class="w-5 flex-shrink-0" />
                                    <span>Add "{{ searchValue }}" as a new Client</span>
                                </div>
                            </div>
                        </ComboboxViewport>
                    </ComboboxContent>
                </ComboboxRoot>
            </UseFocusTrap>
        </template>
    </Dropdown>
</template>

<style scoped></style>
