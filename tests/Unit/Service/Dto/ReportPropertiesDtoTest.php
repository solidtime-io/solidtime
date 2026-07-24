<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Dto;

use App\Enums\TimeEntryType;
use App\Models\Report;
use App\Service\Dto\ReportPropertiesDto;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ReportPropertiesDto::class)]
class ReportPropertiesDtoTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function getBaseProperties(): array
    {
        return [
            'group' => 'project',
            'subGroup' => 'task',
            'historyGroup' => 'day',
            'weekStart' => 'monday',
            'timezone' => 'Europe/Vienna',
            'start' => '2024-01-01T00:00:00Z',
            'end' => '2024-01-31T00:00:00Z',
            'active' => null,
            'memberIds' => null,
            'billable' => null,
            'clientIds' => null,
            'projectIds' => null,
            'tagIds' => null,
            'taskIds' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function castFromJson(array $properties): ReportPropertiesDto
    {
        $json = json_encode($properties);
        $this->assertIsString($json);

        return ReportPropertiesDto::castUsing([])->get(new Report, 'properties', $json, []);
    }

    public function test_time_entry_type_defaults_to_work_if_the_value_is_missing_in_the_persisted_report(): void
    {
        // Arrange
        $properties = $this->getBaseProperties();

        // Act
        $dto = $this->castFromJson($properties);

        // Assert
        $this->assertSame(TimeEntryType::Work, $dto->timeEntryType);
    }

    public function test_time_entry_type_is_null_if_the_persisted_report_has_it_set_to_null(): void
    {
        // Arrange
        $properties = $this->getBaseProperties();
        $properties['timeEntryType'] = null;

        // Act
        $dto = $this->castFromJson($properties);

        // Assert
        $this->assertNull($dto->timeEntryType);
    }

    public function test_time_entry_type_is_casted_to_the_enum_if_the_persisted_report_has_a_value(): void
    {
        // Arrange
        $propertiesWork = $this->getBaseProperties();
        $propertiesWork['timeEntryType'] = 'work';
        $propertiesBreak = $this->getBaseProperties();
        $propertiesBreak['timeEntryType'] = 'break';

        // Act
        $dtoWork = $this->castFromJson($propertiesWork);
        $dtoBreak = $this->castFromJson($propertiesBreak);

        // Assert
        $this->assertSame(TimeEntryType::Work, $dtoWork->timeEntryType);
        $this->assertSame(TimeEntryType::Break, $dtoBreak->timeEntryType);
    }
}
