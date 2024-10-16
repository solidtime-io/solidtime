<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

/**
 * @template T of Model
 */
abstract class CsvExport
{
    private string $disk;

    private string $filename;

    private int $chunk;

    /**
     * @var string[]
     */
    public const array HEADER = [];

    /**
     * @var Builder<T>
     */
    private Builder $builder;

    /**
     * @param  Builder<T>  $builder
     */
    public function __construct(string $disk, string $filename, Builder $builder, int $chunk)
    {

        $this->disk = $disk;
        $this->filename = $filename;
        $this->chunk = $chunk;
        $this->builder = $builder;
    }

    /**
     * @param  T  $model
     * @return array<string, string|Carbon|null>
     */
    abstract public function mapRow(Model $model): array;

    public function export(): void
    {
        $writer = Writer::createFromPath(Storage::disk($this->disk)->path($this->filename), 'w+');
        $writer->insertOne(static::HEADER);

        $this->builder->chunk($this->chunk, function ($models) use ($writer): void {
            foreach ($models as $model) {
                $data = $this->mapRow($model);
                $row = $this->convertRow($data);
                $this->validateRow($row);

                $writer->insertOne(array_values($row));
            }
        });
    }

    /**
     * @param  array<string, string|Carbon|null>  $data
     * @return array<string, string>
     */
    private function convertRow(array $data): array
    {
        $convertedRow = [];
        foreach ($data as $key => $value) {
            if ($value instanceof Carbon) {
                $convertedRow[$key] = $value->toIso8601String();
            } elseif ($value === null) {
                $convertedRow[$key] = '';
            } else {
                $convertedRow[$key] = $value;
            }
        }

        return $convertedRow;
    }

    /**
     * @param  array<string, string>  $row
     *
     * @throws \Exception
     */
    private function validateRow(array $row): void
    {
        if (array_keys($row) !== self::HEADER) {
            throw new \Exception('Invalid row');
        }
    }
}
