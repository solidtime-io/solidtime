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
        if ($roundingType === TimeEntryRoundingType::Down) {
            return 'date_bin(\''.$roundingMinutes.' minutes\', '.$end.', '.$this->getStartSelectRawForRounding($roundingType, $roundingMinutes).')';
        } elseif ($roundingType === TimeEntryRoundingType::Up) {
            return 'date_bin(\''.$roundingMinutes.' minutes\', '.$end.' + interval \''.$roundingMinutes.' minutes\', '.$this->getStartSelectRawForRounding($roundingType, $roundingMinutes).')';
        } elseif ($roundingType === TimeEntryRoundingType::Nearest) {
            return 'date_bin(\''.$roundingMinutes.' minutes\', '.$end.' + interval \''.($roundingMinutes / 2).' minutes\', '.$this->getStartSelectRawForRounding($roundingType, $roundingMinutes).')';
        }
    }
}
