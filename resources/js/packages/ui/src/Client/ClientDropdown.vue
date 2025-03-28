<script setup lang="ts">
import { PlusCircleIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { type Component, computed, nextTick, ref, watch } from 'vue';
import ClientDropdownItem from '@/packages/ui/src/Client/ClientDropdownItem.vue';
import type { CreateClientBody, Client } from '@/packages/api/src';

const model = defineModel<string | null>({
    default: null,
});

const props = defineProps<{
    clients: Client[];
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
}>();

const searchInput = ref<HTMLInputElement | null>(null);
const open = ref(false);
const dropdownViewport = ref<Component | null>(null);

const searchValue = ref('');

function isClientSelected(id: string) {
    return model.value === id;
}

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            searchInput.value?.focus();
        });
    }
});

const filteredClients = computed(() => {
    return props.clients.filter((client) => {
        return client.name
            .toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');
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
        }
    } else {
        if (highlightedItemId.value) {
            model.value = highlightedItemId.value;
        }
    }
}

watch(filteredClients, () => {
    if (filteredClients.value.length > 0) {
        highlightedItemId.value = filteredClients.value[0].id;
    }
});

function updateSearchValue(event: Event) {
    const newInput = (event.target as HTMLInputElement).value;
    if (newInput === ' ') {
        searchValue.value = '';
        const highlightedClientId = highlightedItemId.value;
        if (highlightedClientId) {
            const highlightedClient = props.clients.find(
                (client) => client.id === highlightedClientId
            );
            if (highlightedClient) {
                model.value = highlightedClient.id;
            }
        }
    } else {
        searchValue.value = newInput;
    }
}

const emit = defineEmits(['update:modelValue', 'changed']);

function updateClient(newValue: string) {
    model.value = newValue;
    nextTick(() => {
        emit('changed');
    });
}

function moveHighlightUp() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredClients.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === 0) {
            highlightedItemId.value =
                filteredClients.value[filteredClients.value.length - 1].id;
        } else {
            highlightedItemId.value =
                filteredClients.value[currentHightlightedIndex - 1].id;
        }
    }
}

function moveHighlightDown() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredClients.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === filteredClients.value.length - 1) {
            highlightedItemId.value = filteredClients.value[0].id;
        } else {
            highlightedItemId.value =
                filteredClients.value[currentHightlightedIndex + 1].id;
        }
    }
}

const highlightedItemId = ref<string | null>(null);
const highlightedItem = computed(() => {
    return props.clients.find(
        (client) => client.id === highlightedItemId.value
    );
});
</script>

<template>
    <Dropdown v-model="open" width="120" :close-on-content-click="true">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
        <template #content>
            <input
                ref="searchInput"
                :value="searchValue"
                data-testid="client_dropdown_search"
                class="bg-card-background border-0 placeholder-muted text-sm text-text-primary py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                placeholder="Search for a client..."
                @input="updateSearchValue"
                @keydown.enter="addClientIfNoneExists"
                @keydown.up.prevent="moveHighlightUp"
                @keydown.down.prevent="moveHighlightDown" />
            <div ref="dropdownViewport" class="w-60 max-h-60 overflow-y-scroll">
                <div
                    v-if="
                        searchValue.length > 0 && filteredClients.length === 0
                    "
                    class="bg-card-background-active"
                    @click="addClientIfNoneExists">
                    <div
                        class="flex space-x-3 items-center px-4 py-3 text-xs text-text-primary font-medium border-t rounded-b-lg border-card-background-separator">
                        <PlusCircleIcon
                            class="w-5 flex-shrink-0"></PlusCircleIcon>
                        <span>Add "{{ searchValue }}" as a new Client</span>
                    </div>
                </div>
                <div v-else></div>
                <div
                    v-for="client in filteredClients"
                    :key="client.id"
                    role="option"
                    :value="client.id"
                    :class="{
                        'bg-card-background-active':
                            client.id === highlightedItemId,
                    }"
                    data-testid="client_dropdown_entries"
                    :data-client-id="client.id">
                    <ClientDropdownItem
                        :selected="isClientSelected(client.id)"
                        :name="client.name"
                        @click="updateClient(client.id)"></ClientDropdownItem>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
