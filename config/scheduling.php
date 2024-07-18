<?php

declare(strict_types=1);

return [

    'tasks' => [
        'time_entry_send_still_running_mails' => (bool) env('SCHEDULING_TASK_TIME_ENTRY_SEND_STILL_RUNNING_MAILS', true),
    ],
];
