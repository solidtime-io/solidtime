<script setup lang="ts">
import { computed, inject, ref, watch, type ComputedRef } from 'vue';
import type { Client, Organization } from '@/packages/api/src';
import { XMarkIcon, PlusIcon, TrashIcon } from '@heroicons/vue/20/solid';
import {
    CheckCircleIcon,
    ArchiveBoxIcon,
    PencilSquareIcon,
    ArchiveBoxIcon as ArchiveBoxIconOutline,
    TrashIcon as TrashIconOutline,
} from '@heroicons/vue/24/outline';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import ClientEditModal from '@/Components/Common/Client/ClientEditModal.vue';
import { useClientsStore } from '@/utils/useClients';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { canUpdateClients, canDeleteClients } from '@/utils/permissions';
import { formatDate } from '@/packages/ui/src/utils/time';

const props = defineProps<{
    client: Client;
}>();

const emit = defineEmits<{
    close: [];
}>();

const { projects } = useProjectsQuery();
const organization = inject<ComputedRef<Organization>>('organization');

const projectCount = computed(() => {
    return projects.value.filter((project) => project.client_id === props.client.id).length;
});

const formattedCreatedAt = computed(() => {
    return formatDate(props.client.created_at, organization?.value?.date_format);
});

const showEditModal = ref(false);
const saving = ref(false);

interface MetadataEntry {
    key: string;
    value: string;
}

const metadataEntries = ref<MetadataEntry[]>([]);

function resetMetadataEntries() {
    metadataEntries.value = Object.entries(props.client.metadata ?? {}).map(([key, value]) => ({
        key,
        value,
    }));
}

watch(() => props.client.id, resetMetadataEntries, { immediate: true });

const metadataFromEntries = computed(() => {
    return Object.fromEntries(
        metadataEntries.value
            .filter((entry) => entry.key.trim() !== '')
            .map((entry) => [entry.key.trim(), entry.value])
    );
});

const metadataDirty = computed(() => {
    return (
        JSON.stringify(metadataFromEntries.value) !== JSON.stringify(props.client.metadata ?? {})
    );
});

function addMetadataEntry() {
    metadataEntries.value.push({ key: '', value: '' });
}

function removeMetadataEntry(index: number) {
    metadataEntries.value.splice(index, 1);
}

async function saveMetadata() {
    saving.value = true;
    try {
        await useClientsStore().updateClient(props.client.id, {
            name: props.client.name,
            metadata: metadataFromEntries.value,
        });
    } finally {
        saving.value = false;
    }
}

function archiveClient() {
    useClientsStore().updateClient(props.client.id, {
        ...props.client,
        is_archived: !props.client.is_archived,
    });
}

function deleteClient() {
    useClientsStore().deleteClient(props.client.id);
}
</script>

<template>
    <div
        data-testid="client_detail_pane"
        class="w-[360px] xl:w-[400px] shrink-0 border-l border-default-background-separator sticky top-0 self-start max-h-screen overflow-y-auto">
        <ClientEditModal v-model:show="showEditModal" :client="client"></ClientEditModal>
        <div
            class="flex items-center justify-between px-4 py-4 border-b border-default-background-separator">
            <h2 class="text-text-primary font-semibold text-base truncate pr-3">
                {{ client.name }}
            </h2>
            <button
                aria-label="Close client details"
                class="text-text-secondary hover:text-text-primary hover:bg-card-background rounded-full p-1 transition"
                @click="emit('close')">
                <XMarkIcon class="w-5 h-5"></XMarkIcon>
            </button>
        </div>

        <div class="px-4 py-4 space-y-3 border-b border-default-background-separator text-sm">
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">Status</span>
                <span class="flex items-center space-x-1.5 text-text-primary">
                    <template v-if="client.is_archived">
                        <ArchiveBoxIcon class="w-4 text-icon-default"></ArchiveBoxIcon>
                        <span>Archived</span>
                    </template>
                    <template v-else>
                        <CheckCircleIcon class="w-4 text-icon-default"></CheckCircleIcon>
                        <span>Active</span>
                    </template>
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">Projects</span>
                <span class="text-text-primary">{{ projectCount }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-text-secondary">Created</span>
                <span class="text-text-primary">{{ formattedCreatedAt }}</span>
            </div>
        </div>

        <div
            v-if="canUpdateClients() || canDeleteClients()"
            class="px-4 py-4 flex flex-wrap gap-2 border-b border-default-background-separator">
            <SecondaryButton
                v-if="canUpdateClients()"
                :icon="PencilSquareIcon"
                data-testid="client_detail_rename"
                @click="showEditModal = true"
                >Rename</SecondaryButton
            >
            <SecondaryButton
                v-if="canUpdateClients()"
                :icon="ArchiveBoxIconOutline"
                data-testid="client_detail_archive"
                @click="archiveClient"
                >{{ client.is_archived ? 'Unarchive' : 'Archive' }}</SecondaryButton
            >
            <SecondaryButton
                v-if="canDeleteClients()"
                :icon="TrashIconOutline"
                class="text-destructive"
                data-testid="client_detail_delete"
                @click="deleteClient"
                >Delete</SecondaryButton
            >
        </div>

        <div class="px-4 py-4 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-text-primary font-semibold text-sm">Metadata</h3>
                <SecondaryButton
                    v-if="canUpdateClients()"
                    :icon="PlusIcon"
                    data-testid="client_detail_metadata_add"
                    @click="addMetadataEntry"
                    >Add</SecondaryButton
                >
            </div>
            <p class="text-text-secondary text-xs">
                Key-value pairs to link this client to external systems, f.e. a Stripe customer via
                <span class="font-mono">stripe_customer_id</span>.
            </p>
            <div v-if="metadataEntries.length === 0" class="text-text-secondary text-sm py-2">
                No metadata yet.
            </div>
            <div
                v-for="(entry, index) in metadataEntries"
                :key="index"
                class="flex items-center space-x-2">
                <TextInput
                    v-model="entry.key"
                    :disabled="!canUpdateClients()"
                    type="text"
                    placeholder="Key"
                    class="flex-1 min-w-0 text-sm" />
                <TextInput
                    v-model="entry.value"
                    :disabled="!canUpdateClients()"
                    type="text"
                    placeholder="Value"
                    class="flex-1 min-w-0 text-sm" />
                <button
                    v-if="canUpdateClients()"
                    :aria-label="'Remove metadata entry ' + entry.key"
                    class="text-text-secondary hover:text-destructive p-1 transition"
                    @click="removeMetadataEntry(index)">
                    <TrashIcon class="w-4 h-4"></TrashIcon>
                </button>
            </div>
            <div v-if="metadataDirty && canUpdateClients()" class="pt-1">
                <PrimaryButton
                    :class="{ 'opacity-25': saving }"
                    :disabled="saving"
                    data-testid="client_detail_metadata_save"
                    @click="saveMetadata"
                    >Save Metadata</PrimaryButton
                >
            </div>
        </div>
    </div>
</template>

<style scoped></style>
