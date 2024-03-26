<?php

declare(strict_types=1);

use App\Enums\Weekday;

return [

    'weekday' => [
        Weekday::Monday->value => 'Monday',
        Weekday::Tuesday->value => 'Tuesday',
        Weekday::Wednesday->value => 'Wednesday',
        Weekday::Thursday->value => 'Thursday',
        Weekday::Friday->value => 'Friday',
        Weekday::Saturday->value => 'Saturday',
        Weekday::Sunday->value => 'Sunday',
    ],

];
