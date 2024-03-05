<?php

declare(strict_types=1);

namespace App\Service\Import;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
class ImportDatabaseHelper
{
    /**
     * @var class-string<TModel>
     */
    private string $model;

    /**
     * @var string[]
     */
    private array $identifiers;

    private ?array $mapIdentifierToKey = null;

    private array $mapNewAttach = [];

    private bool $attachToExisting;

    private ?Closure $queryModifier;

    private ?Closure $afterCreate;

    private int $createdCount;

    /**
     * @param  class-string<TModel>  $model
     * @param  array<string>  $identifiers
     */
    public function __construct(string $model, array $identifiers, bool $attachToExisting = false, ?Closure $queryModifier = null, ?Closure $afterCreate = null)
    {
        $this->model = $model;
        $this->identifiers = $identifiers;
        $this->attachToExisting = $attachToExisting;
        $this->queryModifier = $queryModifier;
        $this->afterCreate = $afterCreate;
        $this->createdCount = 0;
    }

    /**
     * @return Builder<TModel>
     */
    private function getModelInstance(): Builder
    {
        return (new $this->model)->query();
    }

    private function createEntity(array $identifierData, array $createValues): string
    {
        $model = new $this->model();
        foreach ($identifierData as $identifier => $identifierValue) {
            $model->{$identifier} = $identifierValue;
        }
        foreach ($createValues as $key => $value) {
            $model->{$key} = $value;
        }
        $model->save();

        if ($this->afterCreate !== null) {
            ($this->afterCreate)($model);
        }

        $this->mapIdentifierToKey[$this->getHash($identifierData)] = $model->getKey();
        $this->createdCount++;

        return $model->getKey();
    }

    private function getHash(array $data): string
    {
        return md5(json_encode($data));
    }

    public function getKey(array $identifierData, array $createValues = []): string
    {
        $this->checkMap();

        $hash = $this->getHash($identifierData);
        if ($this->attachToExisting) {
            $key = $this->mapIdentifierToKey[$hash] ?? null;
            if ($key !== null) {
                return $key;
            }

            return $this->createEntity($identifierData, $createValues);
        } else {
            throw new \RuntimeException('Not implemented');
        }
    }

    private function checkMap(): void
    {
        if ($this->mapIdentifierToKey === null) {
            $select = $this->identifiers;
            $select[] = (new $this->model())->getKeyName();
            $builder = $this->getModelInstance();

            if ($this->queryModifier !== null) {
                $builder = ($this->queryModifier)($builder);
            }

            $databaseEntries = $builder->select($select)
                ->get();
            $this->mapIdentifierToKey = [];
            foreach ($databaseEntries as $databaseEntry) {
                $identifierData = [];
                foreach ($this->identifiers as $identifier) {
                    $identifierData[$identifier] = $databaseEntry->{$identifier};
                }
                $hash = $this->getHash($identifierData);
                $this->mapIdentifierToKey[$hash] = $databaseEntry->getKey();
            }
        }
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }
}
