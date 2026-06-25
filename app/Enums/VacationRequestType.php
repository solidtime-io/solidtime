<?php

declare(strict_types=1);

namespace App\Enums;

use Datomatic\LaravelEnumHelper\LaravelEnumHelper;

enum VacationRequestType: string
{
    use LaravelEnumHelper;

    case RegularVacation = 'regular_vacation';
    case SickDay = 'sick_day';
    case WorkOutside = 'work_outside';
    case Special = 'special';
}
