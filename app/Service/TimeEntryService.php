<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\TimeEntryRoundingType;
use Illuminate\Support\Carbon;
use LogicException;

class TimeEntryService
{
    public function getStartSelectRawForRounding(?TimeEntryRoundingType $roundingType, ?int $roundingMinutes): string
    {
        if ($roundingType === null || $roundingMinutes === null) {
            return 'start';
        }
        if ($roundingMinutes < 1) {
            throw new LogicException('Rounding minutes must be greater than 0');
        }

        return 'date_bin(\'1 minutes\', start, TIMESTAMP \'1970-01-01\')';
    }

    public function getEndSelectRawForRounding(?TimeEntryRoundingType $roundingType, ?int $roundingMinutes): string
    {
        if ($roundingType === null || $roundingMinutes === null) {
            return 'coalesce("end", \''.Carbon::now()->toDateTimeString().'\')';
        }
        if ($roundingMinutes < 1) {
            throw new LogicException('Rounding minutes must be greater than 0');
        }
        $end = 'coalesce("end", \''.Carbon::now()->toDateTimeString().'\')';
        $start = $this->getStartSelectRawForRounding($roundingType, $roundingMinutes);
        if ($roundingType === TimeEntryRoundingType::Down) {
            return 'date_bin(\''.$roundingMinutes.' minutes\', '.$end.', '.$start.')';
        } elseif ($roundingType === TimeEntryRoundingType::Up) {
            // If end is already on a boundary, keep it; otherwise round up to next boundary
            return 'CASE WHEN '.$end.' = date_bin(\''.$roundingMinutes.' minutes\', '.$end.', '.$start.') '.
                   'THEN '.$end.' '.
                   'ELSE date_bin(\''.$roundingMinutes.' minutes\', '.$end.' + interval \''.$roundingMinutes.' minutes\', '.$start.') '.
                   'END';
        } elseif ($roundingType === TimeEntryRoundingType::Nearest) {
            return 'date_bin(\''.$roundingMinutes.' minutes\', '.$end.' + interval \''.($roundingMinutes / 2).' minutes\', '.$start.')';
        }
    }
}
