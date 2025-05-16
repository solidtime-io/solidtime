<?php

declare(strict_types=1);

return [

    'tasks' => [
        'time_entry_send_still_running_mails' => (bool) env('SCHEDULING_TASK_TIME_ENTRY_SEND_STILL_RUNNING_MAILS', true),
        'self_hosting_check_for_update' => (bool) env('SCHEDULING_TASK_SELF_HOSTING_CHECK_FOR_UPDATE', true),
        'self_hosting_telemetry' => (bool) env('SCHEDULING_TASK_SELF_HOSTING_TELEMETRY', true),
        'self_hosting_database_consistency' => (bool) env('SCHEDULING_TASK_SELF_HOSTING_DATABASE_CONSISTENCY', false),
    ],
];
