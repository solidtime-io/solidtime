<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\CurrencyFormat;
use App\Enums\DateFormat;
use App\Enums\IntervalFormat;
use App\Enums\NumberFormat;
use App\Enums\Role;
use App\Enums\TimeFormat;
use App\Models\Organization;
use App\Models\User;

class OrganizationService
{
    public function createOrganization(
        string $name,
        User $owner,
        bool $personalOrganization,
        ?string $currency = null,
        ?NumberFormat $numberFormat = null,
        ?CurrencyFormat $currencyFormat = null,
        ?DateFormat $dateFormat = null,
        ?IntervalFormat $intervalFormat = null,
        ?TimeFormat $timeFormat = null,
    ): Organization {

        $organization = new Organization;
        $organization->name = $name;
        $organization->personal_team = $personalOrganization;
        if ($currency === null) {
            $currency = config('app.localization.default_currency');
        }
        $organization->currency = $currency;
        if ($numberFormat === null) {
            $numberFormat = NumberFormat::from(config('app.localization.default_number_format'));
        }
        $organization->number_format = $numberFormat;
        if ($currencyFormat === null) {
            $currencyFormat = CurrencyFormat::from(config('app.localization.default_currency_format'));
        }
        $organization->currency_format = $currencyFormat;
        if ($dateFormat === null) {
            $dateFormat = DateFormat::from(config('app.localization.default_date_format'));
        }
        $organization->date_format = $dateFormat;
        if ($intervalFormat === null) {
            $intervalFormat = IntervalFormat::from(config('app.localization.default_interval_format'));
        }
        $organization->interval_format = $intervalFormat;
        if ($timeFormat === null) {
            $timeFormat = TimeFormat::from(config('app.localization.default_time_format'));
        }
        $organization->time_format = $timeFormat;
        $organization->owner()->associate($owner);
        $organization->save();

        $organization->users()->attach(
            $owner, [
                'role' => Role::Owner->value,
            ]
        );

        return $organization;
    }
}
