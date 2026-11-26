import { computed, getCurrentInstance, onUnmounted, ref } from 'vue';
import { api } from '@/packages/api/src';
import type { Import, ImportType } from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';
import { initializeStores } from '@/utils/init';

const POLL_INTERVAL_MS = 2000;

export function isActiveImport(item: Import): boolean {
    return item.status === 'pending' || item.status === 'running';
}

function toBase64(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result !== 'string') {
                reject(new Error('Could not read the file'));
                return;
            }
            // Strip the "data:<type>;base64," prefix
            resolve(reader.result.substring(reader.result.indexOf(',') + 1));
        };
        reader.onerror = () => reject(reader.error);
        reader.readAsDataURL(file);
    });
}

/**
 * State of the imports of an organization: the available importers, the recent imports and the running import.
 * Imports are processed in the background, the running import is polled until it is finished.
 */
export function useImports(organizationId: string) {
    const { addNotification, handleApiRequestNotifications } = useNotificationsStore();

    const importers = ref<ImportType[]>([]);
    const imports = ref<Import[]>([]);
    const loaded = ref(false);
    const loadFailed = ref(false);
    const uploading = ref(false);

    const activeImport = computed(() => imports.value.find(isActiveImport) ?? null);

    let stopped = false;
    let polling = false;
    if (getCurrentInstance()) {
        onUnmounted(() => {
            stopped = true;
        });
    }

    function upsert(updatedImport: Import) {
        const index = imports.value.findIndex((item) => item.id === updatedImport.id);
        if (index === -1) {
            imports.value = [updatedImport, ...imports.value];
        } else {
            imports.value[index] = updatedImport;
        }
    }

    /**
     * Loads the importers and the imports. A failed request is shown as a notification and sets loadFailed,
     * so the page can offer to try again instead of loading forever.
     */
    async function load() {
        loadFailed.value = false;
        let responses;
        try {
            responses = await handleApiRequestNotifications(
                () =>
                    Promise.all([
                        api.getImporters({ params: { organization: organizationId } }),
                        api.getImports({ params: { organization: organizationId } }),
                    ]),
                undefined,
                'Could not load the imports'
            );
        } catch {
            // The error was shown as a notification
            loadFailed.value = true;
            return;
        }
        const [importersResponse, importsResponse] = responses;
        importers.value = importersResponse.data;
        imports.value = importsResponse.data;
        loaded.value = true;
        void pollActiveImport();
    }

    async function pollActiveImport() {
        if (polling) {
            return;
        }
        polling = true;
        try {
            while (!stopped && activeImport.value !== null) {
                await new Promise((resolve) => setTimeout(resolve, POLL_INTERVAL_MS));
                const current = activeImport.value;
                if (stopped || current === null) {
                    break;
                }
                try {
                    const updated = (
                        await api.getImport({
                            params: { organization: organizationId, import: current.id },
                        })
                    ).data;
                    upsert(updated);
                    if (updated.status === 'completed') {
                        // The import created clients, projects, tags, ... that the stores do not know yet
                        initializeStores();
                    }
                } catch {
                    // A failed status check (for example a network issue) is retried with the next poll
                }
            }
        } finally {
            polling = false;
        }
    }

    /**
     * Uploads the file and starts the import, the import is processed in the background.
     */
    async function startImport(importer: string, file: File): Promise<Import | null> {
        uploading.value = true;
        try {
            let data: string;
            try {
                data = await toBase64(file);
            } catch {
                addNotification('error', 'Could not read the file');
                return null;
            }
            let response;
            try {
                response = await handleApiRequestNotifications(() =>
                    api.importData(
                        { type: importer, data: data },
                        { params: { organization: organizationId } }
                    )
                );
            } catch {
                // The error was shown as a notification
                return null;
            }
            upsert(response.data);
            if (response.data.status === 'completed') {
                initializeStores();
            }
            void pollActiveImport();
            return response.data;
        } finally {
            uploading.value = false;
        }
    }

    return {
        importers,
        imports,
        loaded,
        loadFailed,
        uploading,
        activeImport,
        load,
        startImport,
    };
}

export type ImportsState = ReturnType<typeof useImports>;
