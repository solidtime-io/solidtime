<?php

declare(strict_types=1);

return [
    'clockify_time_entries' => [
        'name' => 'Clockify Time Entries',
        'description' => 'First make sure that you set the Date format to "MM/DD/YYYY" and the Time format to "12-hour" in the user settings. '.
            'Go to REPORTS -> TIME -> Detailed in the navigation on the left. '.
            'Now select the date range that you want to export in the right top. '.
            'It is currently not possible to select more than one year. You can export each year separately and import them one after another .'.
            'Now click Export -> Save as CSV. The Export dropdown is in the header of the export table left of the printer symbol. '.
            'Before you import make sure that the Timezone settings in Clockify are the same as in solidtime.',
    ],
    'clockify_projects' => [
        'name' => 'Clockify Projects',
        'description' => 'Go to PROJECTS in the navigation on the left. '.
            'Now click on the three dots on the right of the project that you want to export and select Export. '.
            'Now click Export -> Save as CSV. The Export dropdown is in the header of the export table in the top right corner.',
    ],
    'toggl_data_importer' => [
        'name' => 'Toggl Data Importer',
        'description' => 'Go to Admin -> Settings -> Data export. '.
            'Under "Data Export" select all items for export and click on "Export to email". '.
            'You will receive an email with a download link. Download the ZIP and upload it here. '.
            'The "Data Export" exports everything except time entries. '.
            'If you want to also import time entries use the "Toggl Time Entries" importer afterwards.',
    ],
    'toggl_time_entries' => [
        'name' => 'Toggl Time Entries',
        'description' => 'Important: If you want to import a Toggl organization use the "Toggl Data Importer" before using this importer, since this export contains more details. '.
            'Go to Admin -> Settings -> Data export. Under "Time entries" select the year you want to export and click on "Export time entries". You can export all years one after another and import them one after another. '.
            'Before you import make sure that the Timezone settings in Toggl are the same as in solidtime.',
    ],
];
