<?php

declare(strict_types=1);

namespace App\Service\Import\Importers;

use App\Models\Organization;

interface ImporterContract
{
    public function init(Organization $organization): void;

    public function importData(string $data, string $timezone): void;

    public function getReport(): ReportDto;

    public function getName(): string;

    public function getDescription(): string;
}
