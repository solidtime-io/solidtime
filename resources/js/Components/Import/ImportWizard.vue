<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import {
    ArrowUpTrayIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { formatDateTimeLocalized } from '@/packages/ui/src/utils/time';
import { useOrganizationStore } from '@/utils/useOrganization';
import type { Import, ImportReport } from '@/packages/api/src';
import ImportChecklist from './ImportChecklist.vue';
import ImportWizardHeader from './ImportWizardHeader.vue';
import ImportSourceLogo from './ImportSourceLogo.vue';
import {
    buildImportSources,
    findSourceStepForImporter,
    type ImportSource,
    type ImportSourceId,
    type ImportSourceStep,
} from './importSources';
import { isActiveImport, type ImportsState } from './useImports';

const props = defineProps<{
    state: ImportsState;
}>();

const { organization } = storeToRefs(useOrganizationStore());

// Screens of a source: PREPARE, one screen per step (0..n-1) and the summary (n)
const PREPARE = -1;

// The open source, null while the source picker is shown
const sourceId = defineModel<ImportSourceId | null>('source', { default: null });
const screen = ref(PREPARE);
const dragging = ref(false);

const sources = computed(() => buildImportSources(props.state.importers.value));
const source = computed<ImportSource | null>(
    () => sources.value.find((item) => item.id === sourceId.value) ?? null
);
const steps = computed(() => source.value?.steps ?? []);
const isSummary = computed(() => source.value !== null && screen.value >= steps.value.length);
const currentStep = computed<ImportSourceStep | null>(() =>
    screen.value >= 0 && screen.value < steps.value.length ? steps.value[screen.value]! : null
);

function importsOfStep(step: ImportSourceStep): Import[] {
    return props.state.imports.value.filter((item) => item.type === step.importer);
}

function isStepDone(step: ImportSourceStep): boolean {
    return importsOfStep(step).some((item) => item.status === 'completed');
}

/**
 * The screen a source opens with: the step of a running import, otherwise the first step after the last
 * imported step (the summary if all steps are imported), otherwise the preparation screen.
 */
function initialScreen(item: ImportSource): number {
    const active = props.state.activeImport.value;
    if (active !== null) {
        const activeIndex = item.steps.findIndex((step) => step.importer === active.type);
        if (activeIndex !== -1) {
            return activeIndex;
        }
    }
    let lastDone = -1;
    item.steps.forEach((step, index) => {
        if (isStepDone(step)) {
            lastDone = index;
        }
    });
    if (lastDone !== -1) {
        return lastDone + 1;
    }
    return item.prerequisites.length > 0 ? PREPARE : 0;
}

function openSource(item: ImportSource, atScreen?: number) {
    sourceId.value = item.id;
    screen.value = atScreen ?? initialScreen(item);
}

function changeSource() {
    sourceId.value = null;
}

// Step detection: open the step of a running import when the page is opened
watch(
    () => props.state.loaded.value,
    (loaded) => {
        const active = props.state.activeImport.value;
        if (!loaded || active === null || sourceId.value !== null) {
            return;
        }
        const location = findSourceStepForImporter(sources.value, active.type);
        if (location !== null) {
            openSource(location.source, location.stepIndex);
        }
    },
    { immediate: true }
);

/**
 * Suggests to continue a source whose last import is not the last step, for example the Toggl time entries after
 * the Toggl workspace data was imported.
 */
const continueSuggestion = computed(() => {
    const latest = props.state.imports.value[0];
    if (latest === undefined || latest.status !== 'completed') {
        return null;
    }
    const location = findSourceStepForImporter(sources.value, latest.type);
    if (location === null) {
        return null;
    }
    const nextIndex = location.source.steps.findIndex(
        (step, index) => index > location.stepIndex && !isStepDone(step)
    );
    if (nextIndex === -1) {
        return null;
    }
    return {
        source: location.source,
        stepIndex: nextIndex,
        step: location.source.steps[nextIndex]!,
    };
});

const activeImport = computed(() => props.state.activeImport.value);
const activeLocation = computed(() =>
    activeImport.value ? findSourceStepForImporter(sources.value, activeImport.value.type) : null
);
const activeIsOnScreen = computed(
    () =>
        activeLocation.value !== null &&
        activeLocation.value.source.id === sourceId.value &&
        activeLocation.value.stepIndex === screen.value
);
const uploadBlocked = computed(() => activeImport.value !== null || props.state.uploading.value);

const progressSegments = computed(() =>
    steps.value.map((step, index) => ({
        key: step.importer,
        title: step.title,
        done: isStepDone(step),
        current: index === screen.value,
    }))
);

function stepLabel(step: ImportSourceStep): string {
    return step.format === 'File' ? step.title : `${step.title} (${step.format})`;
}

function formatDate(value: string | null): string {
    if (value === null) {
        return '';
    }
    return formatDateTimeLocalized(
        value,
        organization.value?.date_format,
        organization.value?.time_format
    );
}

function formatReport(report: ImportReport | null): string {
    if (report === null) {
        return 'Imported';
    }
    const parts: [number, string, string][] = [
        [report.time_entries.created, 'time entry', 'time entries'],
        [report.projects.created, 'project', 'projects'],
        [report.clients.created, 'client', 'clients'],
        [report.tasks.created, 'task', 'tasks'],
        [report.tags.created, 'tag', 'tags'],
        [report.users.created, 'member', 'members'],
    ];
    const created = parts
        .filter(([count]) => count > 0)
        .map(
            ([count, singular, plural]) =>
                `${count.toLocaleString()} ${count === 1 ? singular : plural}`
        );
    return created.length > 0 ? `Created ${created.join(', ')}` : 'Nothing new to create';
}

function importDescription(item: Import): string {
    switch (item.status) {
        case 'pending':
            return 'Waiting to start…';
        case 'running':
            return 'Importing… You can leave this page, the import continues in the background.';
        case 'completed':
            return formatReport(item.report);
        case 'failed':
            return `Failed, nothing was imported. ${item.error_message ?? ''}`.trim();
    }
    return item.status;
}

// Imports that existed when the page was opened. A step only lists imports of this visit and the ones that ran
// during it, so an import that was already running keeps showing its result. Earlier ones are listed in the
// recent imports.
const importIdsBeforeVisit = ref<Set<string> | null>(null);
const importIdsActiveDuringVisit = ref(new Set<string>());
watch(
    () => props.state.loaded.value,
    (loaded) => {
        if (loaded && importIdsBeforeVisit.value === null) {
            importIdsBeforeVisit.value = new Set(props.state.imports.value.map((item) => item.id));
        }
    },
    { immediate: true }
);
watch(
    () => props.state.activeImport.value?.id,
    (id) => {
        if (id !== undefined) {
            importIdsActiveDuringVisit.value.add(id);
        }
    },
    { immediate: true }
);
const currentStepImports = computed(() =>
    currentStep.value
        ? importsOfStep(currentStep.value)
              .filter(
                  (item) =>
                      isActive(item) ||
                      importIdsActiveDuringVisit.value.has(item.id) ||
                      importIdsBeforeVisit.value?.has(item.id) === false
              )
              .slice(0, 5)
        : []
);
const currentStepDone = computed(() => (currentStep.value ? isStepDone(currentStep.value) : false));

const acceptedFiles = computed(() => {
    switch (currentStep.value?.format) {
        case 'ZIP':
            return '.zip,application/zip';
        case 'CSV':
            return '.csv,text/csv';
    }
    return undefined;
});

const uploadTitle = computed(() => {
    if (props.state.uploading.value) {
        return 'Uploading…';
    }
    if (activeImport.value !== null) {
        return 'Another import is running';
    }
    const step = currentStep.value;
    if (step === null) {
        return '';
    }
    if (currentStepDone.value) {
        return step.repeat ? 'Add the next file' : 'Import another file';
    }
    return step.format === 'File' ? 'Choose the file' : `Choose the ${step.format} file`;
});

const uploadHint = computed(() => {
    if (activeImport.value !== null && !props.state.uploading.value) {
        return activeIsOnScreen.value
            ? 'The next file can be added when this import has finished.'
            : 'Files can be added when it has finished. You can still go through the other steps.';
    }
    return 'Drop it here or click to choose. It is imported right away.';
});

async function importFile(file: File | undefined) {
    const step = currentStep.value;
    if (file === undefined || step === null || uploadBlocked.value) {
        return;
    }
    await props.state.startImport(step.importer, file);
}

function onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    void importFile(input.files?.[0]);
    input.value = '';
}

function onDragLeave(event: DragEvent) {
    const currentTarget = event.currentTarget as HTMLElement | null;
    if (currentTarget && currentTarget.contains(event.relatedTarget as Node | null)) {
        return;
    }
    dragging.value = false;
}

function onDrop(event: DragEvent) {
    dragging.value = false;
    void importFile(event.dataTransfer?.files?.[0]);
}

function goBack() {
    const firstScreen = source.value?.prerequisites.length ? PREPARE : 0;
    if (screen.value <= firstScreen) {
        changeSource();
        return;
    }
    screen.value = Math.min(screen.value, steps.value.length) - 1;
}

// Skipping a step without importing a file is confirmed first, since the data of that step will be missing
const showSkipConfirmation = ref(false);
const skipNeedsConfirmation = computed(() => {
    const step = currentStep.value;
    if (step === null || currentStepDone.value) {
        return false;
    }
    return !importsOfStep(step).some((item) => isActive(item));
});

function goNext() {
    if (isSummary.value) {
        changeSource();
        return;
    }
    if (skipNeedsConfirmation.value) {
        showSkipConfirmation.value = true;
        return;
    }
    screen.value += 1;
}

function confirmSkip() {
    showSkipConfirmation.value = false;
    screen.value += 1;
}

const isLastStep = computed(() => screen.value === steps.value.length - 1);

const nextLabel = computed(() => {
    if (isSummary.value) {
        return 'Import from another tool';
    }
    if (screen.value === PREPARE) {
        return 'Continue';
    }
    if (currentStepDone.value) {
        return isLastStep.value ? 'Finish' : 'Next';
    }
    return isLastStep.value ? 'Skip and finish' : 'Skip';
});

const summaryRows = computed(() =>
    steps.value.map((step) => ({
        step,
        imports: importsOfStep(step).filter((item) => item.status === 'completed'),
    }))
);
const summaryHasImports = computed(() => summaryRows.value.some((row) => row.imports.length > 0));
const summaryCreatedMembers = computed(() =>
    summaryRows.value.some((row) =>
        row.imports.some((item) => (item.report?.users.created ?? 0) > 0)
    )
);

function showActiveImport() {
    if (activeLocation.value !== null) {
        openSource(activeLocation.value.source, activeLocation.value.stepIndex);
    }
}

function isActive(item: Import): boolean {
    return isActiveImport(item);
}
</script>

<template>
    <div class="flex flex-col" data-testid="import_wizard">
        <!-- Header of the selected source -->
        <ImportWizardHeader
            v-if="source"
            :source="source"
            :segments="progressSegments"
            :screen="screen"
            @change-source="changeSource" />

        <!-- Running import that belongs to another step or source -->
        <div
            v-if="activeImport && !activeIsOnScreen"
            data-testid="import_in_progress"
            class="px-4 sm:px-5 py-2.5 text-sm flex items-center gap-3 border-b border-card-background-separator">
            <LoadingSpinner class="h-4 w-4 m-0" />
            <p class="flex-1 text-text-secondary">
                <template v-if="activeLocation">
                    {{ activeLocation.source.name }}
                    {{ activeLocation.source.steps[activeLocation.stepIndex]?.title.toLowerCase() }}
                    {{
                        activeImport.status === 'running'
                            ? 'are being imported'
                            : 'are waiting to be imported'
                    }}.
                </template>
                <template v-else>An import is running.</template>
            </p>
            <button
                v-if="activeLocation"
                type="button"
                class="text-sm text-text-primary hover:underline"
                @click="showActiveImport">
                Show
            </button>
        </div>

        <div class="px-4 sm:px-5 py-5 space-y-4 text-sm">
            <div
                v-if="state.loadFailed.value"
                data-testid="import_load_failed"
                class="flex flex-col items-center gap-3 py-10 text-center">
                <p class="text-text-secondary">The imports could not be loaded.</p>
                <SecondaryButton @click="state.load()">Try again</SecondaryButton>
            </div>
            <div v-else-if="!state.loaded.value" class="flex justify-center py-10">
                <LoadingSpinner />
            </div>

            <!-- Source picker -->
            <template v-else-if="!source">
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-text-primary">
                        Where is your data now?
                    </h3>
                    <p class="text-text-secondary">
                        Pick the tool. We show you what to export there and in which order to import
                        the files.
                    </p>
                </div>
                <button
                    v-if="continueSuggestion"
                    type="button"
                    data-testid="import_continue_suggestion"
                    class="w-full flex items-center gap-3 rounded-lg border border-card-border bg-card-background-active px-3 py-2.5 text-left hover:border-card-border-active"
                    @click="openSource(continueSuggestion.source, continueSuggestion.stepIndex)">
                    <ImportSourceLogo :source="continueSuggestion.source.id" size="small" />
                    <span class="flex-1 min-w-0">
                        <span class="block font-medium text-text-primary">
                            Continue with {{ continueSuggestion.source.name }}
                        </span>
                        <span class="block text-xs text-text-secondary">
                            Next: {{ stepLabel(continueSuggestion.step) }}
                        </span>
                    </span>
                    <span class="text-text-secondary" aria-hidden="true">→</span>
                </button>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <button
                        v-for="item in sources"
                        :key="item.id"
                        type="button"
                        :data-testid="`import_source_${item.id}`"
                        class="flex items-center gap-3 rounded-lg border border-card-border bg-card-background px-3 py-2.5 text-left hover:border-card-border-active hover:bg-card-background-active transition"
                        @click="openSource(item)">
                        <ImportSourceLogo :source="item.id" />
                        <span class="min-w-0">
                            <span class="block font-semibold text-text-primary">{{
                                item.name
                            }}</span>
                            <span class="block text-xs text-text-secondary">{{ item.blurb }}</span>
                        </span>
                    </button>
                </div>
                <p class="text-xs text-text-tertiary">
                    Using another tool? Export a CSV and use Generic CSV.
                </p>
            </template>

            <!-- Preparation -->
            <template v-else-if="screen === PREPARE">
                <h3 class="text-base font-semibold text-text-primary">
                    Before you export from {{ source.name }}
                </h3>
                <ImportChecklist :items="source.prerequisites" />
            </template>

            <!-- Step -->
            <template v-else-if="currentStep">
                <div class="space-y-1">
                    <div class="text-xs font-medium text-accent-300">
                        File {{ screen + 1 }} of {{ steps.length }}
                        <template v-if="currentStep.format !== 'File'">
                            · {{ currentStep.format }}</template
                        >
                    </div>
                    <h3 class="text-base font-semibold text-text-primary">
                        <template v-if="currentStep.format === 'File'">{{
                            currentStep.title
                        }}</template>
                        <template v-else>Export the {{ currentStep.title.toLowerCase() }}</template>
                    </h3>
                    <p v-if="currentStep.note" class="max-w-md text-text-secondary">
                        {{ currentStep.note }}
                    </p>
                </div>
                <ImportChecklist
                    v-if="currentStep.instructions.length > 0"
                    :items="currentStep.instructions" />

                <label
                    :class="[
                        'flex max-w-md flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed px-4 text-center transition',
                        currentStepImports.length > 0 ? 'py-5' : 'py-8',
                        // Below the instructions it is indented like their previews
                        currentStep.instructions.length > 0 && 'ml-[1.875rem]',
                        uploadBlocked
                            ? 'border-card-border bg-card-background cursor-not-allowed opacity-70'
                            : dragging
                              ? 'border-accent-400 bg-card-background-active cursor-pointer'
                              : 'border-border-secondary bg-card-background-active hover:border-text-quaternary cursor-pointer',
                    ]"
                    @dragover.prevent="dragging = !uploadBlocked"
                    @dragleave="onDragLeave"
                    @drop.prevent="onDrop">
                    <span
                        class="mb-1 flex h-10 w-10 items-center justify-center rounded-full bg-card-background text-text-secondary">
                        <LoadingSpinner v-if="state.uploading.value" class="h-5 w-5 m-0" />
                        <ArrowUpTrayIcon v-else class="h-5 w-5" />
                    </span>
                    <span class="text-base font-semibold text-text-primary">{{ uploadTitle }}</span>
                    <span class="text-xs text-text-secondary">{{ uploadHint }}</span>
                    <input
                        type="file"
                        class="sr-only"
                        data-testid="import_file_input"
                        :accept="acceptedFiles"
                        :disabled="uploadBlocked"
                        @change="onFileSelected" />
                </label>

                <ul
                    v-if="currentStepImports.length > 0"
                    :class="[
                        'max-w-md space-y-1.5',
                        currentStep.instructions.length > 0 && 'ml-[1.875rem]',
                    ]"
                    data-testid="import_step_imports">
                    <li
                        v-for="item in currentStepImports"
                        :key="item.id"
                        :class="[
                            'flex items-start gap-2.5 rounded-lg border px-3 py-2',
                            item.status === 'failed'
                                ? 'border-yellow-600/40 bg-yellow-500/5'
                                : 'border-card-border bg-card-background dark:bg-card-background-active',
                        ]">
                        <LoadingSpinner v-if="isActive(item)" class="h-4 w-4 m-0 mt-0.5" />
                        <CheckCircleIcon
                            v-else-if="item.status === 'completed'"
                            class="h-4 w-4 flex-none mt-0.5 text-green-500" />
                        <ExclamationTriangleIcon
                            v-else
                            class="h-4 w-4 flex-none mt-0.5 text-yellow-500" />
                        <span class="flex-1 min-w-0">
                            <span class="block text-xs text-text-tertiary">{{
                                formatDate(item.created_at)
                            }}</span>
                            <span class="block text-text-primary">{{
                                importDescription(item)
                            }}</span>
                        </span>
                    </li>
                </ul>
            </template>

            <!-- Summary -->
            <template v-else-if="isSummary">
                <div class="space-y-1">
                    <h3 class="text-base font-semibold text-text-primary">
                        <template v-if="summaryHasImports"
                            >Import from {{ source.name }} done</template
                        >
                        <template v-else>Nothing imported from {{ source.name }} yet</template>
                    </h3>
                    <p class="text-text-secondary">
                        <template v-if="summaryHasImports"
                            >This is what was imported for each step.</template
                        >
                        <template v-else>Go back to a step and add its file to import it.</template>
                    </p>
                </div>
                <ul class="space-y-1.5">
                    <li
                        v-for="(row, index) in summaryRows"
                        :key="row.step.importer"
                        class="flex items-start gap-2.5 rounded-lg border border-card-border bg-card-background dark:bg-card-background-active px-3 py-2">
                        <CheckCircleIcon
                            v-if="row.imports.length > 0"
                            class="h-4 w-4 flex-none mt-0.5 text-green-500" />
                        <span
                            v-else
                            class="h-4 w-4 flex-none mt-0.5 rounded-full border border-border-secondary"></span>
                        <span class="flex-1 min-w-0">
                            <span class="block font-medium text-text-primary">{{
                                stepLabel(row.step)
                            }}</span>
                            <span
                                v-for="item in row.imports"
                                :key="item.id"
                                class="block text-text-secondary">
                                {{ formatReport(item.report) }}
                                <span class="text-xs text-text-tertiary"
                                    >· {{ formatDate(item.created_at) }}</span
                                >
                            </span>
                            <span v-if="row.imports.length === 0" class="block text-text-secondary">
                                Not imported.
                                <button
                                    type="button"
                                    class="underline hover:text-text-primary"
                                    @click="screen = index">
                                    Go to step
                                </button>
                            </span>
                        </span>
                    </li>
                </ul>
                <p v-if="summaryCreatedMembers" class="text-text-secondary">
                    People from the files were added as placeholder members. Invite them from
                    Members so they can see their time entries.
                </p>
            </template>
        </div>

        <div
            v-if="source"
            class="flex items-center gap-2 px-4 py-3 bg-card-background border-t border-card-background-separator sm:px-5">
            <SecondaryButton data-testid="import_wizard_back" @click="goBack">Back</SecondaryButton>
            <span class="flex-1"></span>
            <PrimaryButton type="button" data-testid="import_wizard_next" @click="goNext">
                {{ nextLabel }}
            </PrimaryButton>
        </div>

        <DialogModal
            :show="showSkipConfirmation"
            max-width="lg"
            @close="showSkipConfirmation = false">
            <template #title>Skip without importing?</template>
            <template #content>
                <p v-if="currentStep && source">
                    You did not import the {{ currentStep.title.toLowerCase() }} from
                    {{ source.name }} yet.
                    <template v-if="currentStep.skipWarning">
                        {{ currentStep.skipWarning }}
                    </template>
                    <template v-else>
                        If you skip this step they will be missing in solidtime.
                    </template>
                    <template v-if="isLastStep">You can come back to it later.</template>
                    <template v-else>
                        Import it before the next steps, a later import does not update what already
                        exists in solidtime.
                    </template>
                </p>
            </template>
            <template #footer>
                <SecondaryButton @click="showSkipConfirmation = false">Go back</SecondaryButton>
                <PrimaryButton
                    class="ms-3"
                    data-testid="import_wizard_skip_confirm"
                    @click="confirmSkip">
                    {{ nextLabel }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </div>
</template>
