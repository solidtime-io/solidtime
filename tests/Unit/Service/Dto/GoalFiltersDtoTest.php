<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Dto;

use App\Enums\TagMatchType;
use App\Enums\TimeEntryType;
use App\Models\Goal;
use App\Service\Dto\GoalFiltersDto;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(GoalFiltersDto::class)]
class GoalFiltersDtoTest extends TestCase
{
    public function test_get_throws_when_json_is_missing_a_required_property(): void
    {
        // Arrange
        $caster = GoalFiltersDto::castUsing([]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given JSON string does not contain the required property "memberIds"');

        // Act
        $caster->get(new Goal, 'filters', '{}', []);
    }

    public function test_get_returns_null_filters_when_all_properties_are_null(): void
    {
        // Arrange
        $caster = GoalFiltersDto::castUsing([]);
        $json = '{"memberIds":null,"projectIds":null,"taskIds":null,"tagIds":null,"tagMatchType":null,"clientIds":null,"billable":null,"timeEntryType":null}';

        // Act
        $dto = $caster->get(new Goal, 'filters', $json, []);

        // Assert
        $this->assertNull($dto->memberIds);
        $this->assertNull($dto->projectIds);
        $this->assertNull($dto->taskIds);
        $this->assertNull($dto->tagIds);
        $this->assertNull($dto->tagMatchType);
        $this->assertNull($dto->clientIds);
        $this->assertNull($dto->billable);
        $this->assertNull($dto->timeEntryType);
    }

    public function test_set_and_get_round_trip_keeps_all_values(): void
    {
        // Arrange
        $caster = GoalFiltersDto::castUsing([]);
        $dto = new GoalFiltersDto;
        $dto->setProjectIds(['none']);
        $dto->setTaskIds(['9e2a7a1e-5a5e-4a0c-9d6b-3d5b2a1c0f11']);
        $dto->setTagIds([]);
        $dto->tagMatchType = TagMatchType::Contains;
        $dto->setClientIds(['9e2a7a1e-5a5e-4a0c-9d6b-3d5b2a1c0f13']);
        $dto->billable = false;
        $dto->timeEntryType = TimeEntryType::Work;

        // Act
        $json = $caster->set(new Goal, 'filters', $dto, []);
        $result = $caster->get(new Goal, 'filters', $json, []);

        // Assert
        $this->assertSame(['none'], $result->projectIds?->toArray());
        $this->assertSame(['9e2a7a1e-5a5e-4a0c-9d6b-3d5b2a1c0f11'], $result->taskIds?->toArray());
        $this->assertNull($result->tagIds);
        $this->assertSame(TagMatchType::Contains, $result->tagMatchType);
        $this->assertSame(['9e2a7a1e-5a5e-4a0c-9d6b-3d5b2a1c0f13'], $result->clientIds?->toArray());
        $this->assertFalse($result->billable);
        $this->assertSame(TimeEntryType::Work, $result->timeEntryType);
    }

    public function test_get_throws_for_invalid_json(): void
    {
        // Arrange
        $caster = GoalFiltersDto::castUsing([]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $caster->get(new Goal, 'filters', 'not json', []);
    }

    public function test_id_setters_reject_invalid_ids(): void
    {
        // Arrange
        $dto = new GoalFiltersDto;

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $dto->setProjectIds(['not-a-uuid']);
    }
}
