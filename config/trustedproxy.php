<?php

declare(strict_types=1);

return [
    'proxies' => ! is_string(env('TRUSTED_PROXIES', null)) ? [] : explode(',', env('TRUSTED_PROXIES')),
];
