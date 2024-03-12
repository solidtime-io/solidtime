<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Service\Import\Importers\ImportException;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

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

    /**
     * @var array<string, string>|null
     */
    private ?array $mapIdentifierToKey = null;

    /**
     * @var array<string, string>
     */
    private array $mapExternalIdentifierToInternalIdentifier = [];

    private bool $attachToExisting;

    private ?Closure $queryModifier;

    private ?Closure $afterCreate;

    private int $createdCount;

    /**
     * @var array<string, array<int, string>>
     */
    private array $validate;

    /**
     * @param  class-string<TModel>  $model
     * @param  array<string>  $identifiers
     * @param  array<string, array<int, string>>  $validate
     */
    public function __construct(string $model, array $identifiers, bool $attachToExisting = false, ?Closure $queryModifier = null, ?Closure $afterCreate = null, array $validate = [])
    {
        $this->model = $model;
        $this->identifiers = $identifiers;
        $this->attachToExisting = $attachToExisting;
        $this->queryModifier = $queryModifier;
        $this->afterCreate = $afterCreate;
        $this->createdCount = 0;
        $this->validate = $validate;
    }

    /**
     * @return Builder<TModel>
     */
    private function getModelInstance(): Builder
    {
        return (new $this->model)->query();
    }

    /**
     * @param  array<string, mixed>  $identifierData
     * @param  array<string, mixed>  $createValues
     */
    private function createEntity(array $identifierData, array $createValues, ?string $externalIdentifier): string
    {
        $data = array_merge($identifierData, $createValues);

        $validator = Validator::make($data, $this->validate);
        if ($validator->fails()) {
            throw new ImportException('Invalid data: '.implode(', ', $validator->errors()->all()));
        }

        $model = new $this->model();
        foreach ($data as $key => $value) {
            $model->{$key} = $value;
        }
        $model->save();

        if ($this->afterCreate !== null) {
            ($this->afterCreate)($model);
        }

        $hash = $this->getHash($identifierData);
        $this->mapIdentifierToKey[$hash] = $model->getKey();
        $this->createdCount++;

        if ($externalIdentifier !== null) {
            $this->mapExternalIdentifierToInternalIdentifier[$externalIdentifier] = $hash;
        }

        return $model->getKey();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function getHash(array $data): string
    {
        $jsonData = json_encode($data);
        if ($jsonData === false) {
            throw new \RuntimeException('Failed to encode data to JSON');
        }

        return md5($jsonData);
    }

    /**
     * @param  array<string, mixed>  $identifierData
     * @param  array<string, mixed>  $createValues
     *
     * @throws ImportException
     */
    public function getKey(array $identifierData, array $createValues = [], ?string $externalIdentifier = null): string
    {
        $this->checkMap();

        $this->validateIdentifierData($identifierData);

        $hash = $this->getHash($identifierData);
        if ($this->attachToExisting) {
            $key = $this->mapIdentifierToKey[$hash] ?? null;
            if ($key !== null) {
                if ($externalIdentifier !== null) {
                    $this->mapExternalIdentifierToInternalIdentifier[$externalIdentifier] = $hash;
                }

                return $key;
            }

            return $this->createEntity($identifierData, $createValues, $externalIdentifier);
        } else {
            throw new \RuntimeException('Not implemented');
        }
    }

    /**
     * @param  array<string, mixed>  $identifierData
     *
     * @throws ImportException
     */
    private function validateIdentifierData(array $identifierData): void
    {
        if (array_keys($identifierData) !== $this->identifiers) {
            throw new ImportException('Invalid identifier data');
        }
    }

    public function getKeyByExternalIdentifier(string $externalIdentifier): ?string
    {
        $hash = $this->mapExternalIdentifierToInternalIdentifier[$externalIdentifier] ?? null;
        if ($hash === null) {
            return null;
        }

        return $this->mapIdentifierToKey[$hash] ?? null;
    }

    /**
     * @return array<string>
     */
    public function getExternalIds(): array
    {
        // Note: Otherwise the external ids are integers
        return array_map(fn ($value) => (string) $value, array_keys($this->mapExternalIdentifierToInternalIdentifier));
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
