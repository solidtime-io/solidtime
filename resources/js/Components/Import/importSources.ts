import type { ImportType } from '@/packages/api/src';
import type { Component } from 'vue';
import ClockifyFormatPreview from './ClockifyFormatPreview.vue';
import ClockifyProjectExportPreview from './ClockifyProjectExportPreview.vue';
import ClockifyProjectsTablePreview from './ClockifyProjectsTablePreview.vue';
import ClockifyTimezonePreview from './ClockifyTimezonePreview.vue';
import HarvestClientsExportPreview from './HarvestClientsExportPreview.vue';
import HarvestProjectsExportPreview from './HarvestProjectsExportPreview.vue';
import HarvestProjectsPagePreview from './HarvestProjectsPagePreview.vue';
import HarvestTimeExportPreview from './HarvestTimeExportPreview.vue';
import TogglDataExportPreview from './TogglDataExportPreview.vue';
import TogglTimeEntriesExportPreview from './TogglTimeEntriesExportPreview.vue';
import TogglTimezonePreview from './TogglTimezonePreview.vue';

export type ImportSourceId = 'toggl' | 'clockify' | 'harvest' | 'solidtime' | 'generic' | 'other';

export interface ImportSourceStep {
    // Importer key of the backend, see ImporterProvider
    importer: string;
    title: string;
    format: 'ZIP' | 'CSV' | 'File';
    // What is missing in solidtime if the step is skipped, replaces the general text of the skip confirmation
    skipWarning?: string;
    // The source exports one file per year, several files can be imported in this step
    repeat?: boolean;
    note: string;
    instructions: (string | ImportListItem)[];
}

export interface ImportListItem {
    // Text wrapped in ** is shown bold
    text: string;
    link?: { href: string; label: string };
    // Recreation of the setting in the source tool, shown below the text
    preview?: Component;
}

export interface ImportSource {
    id: ImportSourceId;
    name: string;
    blurb: string;
    prerequisites: (string | ImportListItem)[];
    steps: ImportSourceStep[];
}

const TOGGL_DATA_EXPORT_LINK = {
    href: 'https://track.toggl.com/settings/export',
    label: 'Open Toggl data export',
};

const CLOCKIFY_PREFERENCES_LINK = {
    href: 'https://app.clockify.me/user/preferences',
    label: 'Open Clockify preferences',
};

/**
 * Known import sources with their exports in the order they have to be imported.
 * Files that contain the organization structure (clients, projects, ...) come before the time entries,
 * since they contain details (billable rates, estimates, ...) that the time entry exports do not contain.
 */
const KNOWN_SOURCES: ImportSource[] = [
    {
        id: 'toggl',
        name: 'Toggl Track',
        blurb: 'Workspace data, then time entries per year',
        prerequisites: [
            'Sign in to Toggl as an **Admin of the workspace**. Other members can only export their own time entries.',
            {
                text: 'Set the **same timezone in Toggl as in solidtime**',
                link: {
                    href: 'https://track.toggl.com/account',
                    label: 'Open Toggl account settings',
                },
                preview: TogglTimezonePreview,
            },
        ],
        steps: [
            {
                importer: 'toggl_data_importer',
                title: 'Workspace data',
                format: 'ZIP',
                note: 'Clients, projects, tasks, tags and members. Time entries are exported in the next step.',
                instructions: [
                    {
                        text: 'In Toggl open Admin, then Settings, then Data export',
                        link: TOGGL_DATA_EXPORT_LINK,
                    },
                    {
                        text: 'Under "Data export" select all items and click "Export to email"',
                        preview: TogglDataExportPreview,
                    },
                    'Download the ZIP from the email (log in to Toggl first) and add it here',
                ],
            },
            {
                importer: 'toggl_time_entries',
                title: 'Time entries',
                format: 'CSV',
                repeat: true,
                note: 'Toggl exports one file per year. Import every year you want to bring over.',
                instructions: [
                    {
                        text: 'On the same Data export page find "Time entries"',
                        link: TOGGL_DATA_EXPORT_LINK,
                    },
                    {
                        text: 'Select a year and click "Export time entries"',
                        preview: TogglTimeEntriesExportPreview,
                    },
                    'Add the file here and repeat for each year',
                ],
            },
        ],
    },
    {
        id: 'clockify',
        name: 'Clockify',
        blurb: 'Projects, then time entries per year',
        prerequisites: [
            {
                text: 'Under **General in the Clockify preferences** set the language to **English**, the date format to **MM/DD/YYYY** and the time format to **12-hour**',
                link: CLOCKIFY_PREFERENCES_LINK,
                preview: ClockifyFormatPreview,
            },
            {
                text: 'Set the **same timezone in Clockify as in solidtime**',
                link: CLOCKIFY_PREFERENCES_LINK,
                preview: ClockifyTimezonePreview,
            },
            'Check that your Clockify plan includes **CSV exports** (Basic or higher)',
        ],
        steps: [
            {
                importer: 'clockify_projects',
                title: 'Projects',
                format: 'CSV',
                note: '',
                skipWarning:
                    'Billable rates, billable status and estimates of the projects are not in the time report and will be missing in solidtime.',
                instructions: [
                    {
                        text: 'Open Projects in the left navigation, click **Export** at the top right of the projects table, then **Customize...**',
                        link: {
                            href: 'https://app.clockify.me/projects',
                            label: 'Open Clockify projects',
                        },
                        preview: ClockifyProjectsTablePreview,
                    },
                    {
                        text: 'Make sure **all columns are selected** and click Save',
                        preview: ClockifyProjectExportPreview,
                    },
                    'Click Export again, then **Save as CSV**, and add the file here',
                ],
            },
            {
                importer: 'clockify_time_entries',
                title: 'Time entries',
                format: 'CSV',
                repeat: true,
                note: 'The detailed report covers at most one year per file. Import one file per year.',
                instructions: [
                    'Open Reports, then Time, then Detailed',
                    'Pick a date range of up to one year (top right)',
                    'Click Export, then Save as CSV, and add the file here',
                ],
            },
        ],
    },
    {
        id: 'harvest',
        name: 'Harvest',
        blurb: 'Clients, projects, then all time entries',
        prerequisites: [
            'Sign in to Harvest as an **Administrator**',
            'Set the **same timezone in Harvest as in solidtime**',
        ],
        steps: [
            {
                importer: 'harvest_clients',
                title: 'Clients',
                format: 'CSV',
                note: '',
                skipWarning:
                    'Clients that have no projects or time entries will be missing in solidtime.',
                instructions: [
                    {
                        text: 'Open Clients in the left navigation, click **Import/Export** at the top right, then **Export clients**',
                        link: {
                            href: 'https://harvestapp.com/clients',
                            label: 'Open Harvest clients',
                        },
                        preview: HarvestClientsExportPreview,
                    },
                    'Add the downloaded CSV file here',
                ],
            },
            {
                importer: 'harvest_projects',
                title: 'Projects',
                format: 'CSV',
                note: '',
                skipWarning:
                    'Billable status and estimates of the projects are not in the time entries export and will be missing in solidtime.',
                instructions: [
                    {
                        text: 'Open Projects in the left navigation and click **Export** at the top right',
                        link: {
                            href: 'https://harvestapp.com/projects',
                            label: 'Open Harvest projects',
                        },
                        preview: HarvestProjectsPagePreview,
                    },
                    {
                        text: 'Select **Active**, choose **CSV** and click **Export projects**. Harvest sends the file by email.',
                        preview: HarvestProjectsExportPreview,
                    },
                    'Download the file from the email and add it here',
                ],
            },
            {
                importer: 'harvest_time_entries',
                title: 'Time entries',
                format: 'CSV',
                note: 'One file with all time entries. Large accounts get it by email.',
                instructions: [
                    {
                        text: 'Open Settings, then **Import/Export**, and click **Export all time** under "Export data"',
                        link: {
                            href: 'https://harvestapp.com/company/settings/import_export',
                            label: 'Open Harvest import/export settings',
                        },
                        preview: HarvestTimeExportPreview,
                    },
                    'Add the downloaded CSV file here',
                ],
            },
        ],
    },
    {
        id: 'solidtime',
        name: 'solidtime',
        blurb: 'Another instance or organization',
        prerequisites: [
            'Sign in to the other solidtime as **Admin or Owner** of the organization you export',
        ],
        steps: [
            {
                importer: 'solidtime',
                title: 'Organization data',
                format: 'ZIP',
                note: 'Everything in one file: members, clients, projects, tasks, tags and time entries.',
                instructions: [
                    'In the other solidtime instance select the organization (top left)',
                    'Open Import / Export in the navigation',
                    'Click "Export Organization Data" and add the ZIP here',
                ],
            },
        ],
    },
    {
        id: 'generic',
        name: 'Generic CSV',
        blurb: 'Your own spreadsheet',
        prerequisites: [
            'Save the file **comma separated** and **UTF-8 encoded**',
            'Write all times in **UTC as ISO 8601**, for example 2024-03-04T09:23:00Z',
        ],
        steps: [
            {
                importer: 'generic_projects',
                title: 'Projects',
                format: 'CSV',
                note: 'Columns: name, color, billable_rate (in cents), is_public, client, billable_default, estimated_time (in seconds), archived_at.',
                skipWarning:
                    'Colors, billable rates, visibility, estimates and the archived state of the projects will be missing in solidtime.',
                instructions: [
                    {
                        text: 'Create a CSV with the columns above, one project per row. The docs describe every column and its format.',
                        link: {
                            href: 'https://docs.solidtime.io/user-guide/import#generic-projects-importer',
                            label: 'Open the docs',
                        },
                    },
                ],
            },
            {
                importer: 'generic_time_entries',
                title: 'Time entries',
                format: 'CSV',
                note: 'Columns: description, billable, client, project, tags, start, end, task, user_name, user_email.',
                instructions: [
                    {
                        text: 'Create a CSV with the columns above, one time entry per row. The docs describe every column and its format.',
                        link: {
                            href: 'https://docs.solidtime.io/user-guide/import#generic-time-entries-importer',
                            label: 'Open the docs',
                        },
                    },
                ],
            },
        ],
    },
];

/**
 * Builds the sources for the importers available on this instance. Steps of importers that are not available are
 * removed, importers that are not part of a known source (for example from extensions) are grouped in "Other".
 */
export function buildImportSources(importers: ImportType[]): ImportSource[] {
    const available = new Set(importers.map((importer) => importer.key));
    const sources = KNOWN_SOURCES.map((source) => ({
        ...source,
        steps: source.steps.filter((step) => available.has(step.importer)),
    })).filter((source) => source.steps.length > 0);

    const knownImporters = new Set(
        KNOWN_SOURCES.flatMap((source) => source.steps.map((step) => step.importer))
    );
    const otherImporters = importers.filter((importer) => !knownImporters.has(importer.key));
    if (otherImporters.length > 0) {
        sources.push({
            id: 'other',
            name: 'Other',
            blurb: 'More importers',
            prerequisites: [],
            steps: otherImporters.map((importer) => ({
                importer: importer.key,
                title: importer.name,
                format: 'File',
                note: '',
                instructions: [],
            })),
        });
    }
    return sources;
}

export function findSourceStepForImporter(
    sources: ImportSource[],
    importer: string
): { source: ImportSource; stepIndex: number } | null {
    for (const source of sources) {
        const stepIndex = source.steps.findIndex((step) => step.importer === importer);
        if (stepIndex !== -1) {
            return { source, stepIndex };
        }
    }
    return null;
}
