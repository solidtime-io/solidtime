<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Spatie\TemporaryDirectory\TemporaryDirectory;

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

    private string $folderPath;

    /**
     * @param  Builder<T>  $builder
     */
    public function __construct(string $disk, string $folderPath, string $filename, Builder $builder, int $chunk)
    {

        $this->disk = $disk;
        $this->filename = $filename;
        $this->chunk = $chunk;
        $this->builder = $builder;
        $this->folderPath = $folderPath;
    }

    /**
     * @param  T  $model
     * @return array<string, string|Carbon|null>
     */
    abstract public function mapRow(Model $model): array;

    /**
     * @throws \League\Csv\CannotInsertRecord
     * @throws \League\Csv\Exception
     * @throws \League\Csv\UnavailableStream
     */
    public function export(): void
    {
        $tempDirectory = TemporaryDirectory::make();
        $writer = Writer::createFromPath($tempDirectory->path($this->filename), 'w+');
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $writer->setEscape('');
        $writer->insertOne(static::HEADER);

        $this->builder->chunk($this->chunk, function (Collection $models) use ($writer): void {
            foreach ($models as $model) {
                $data = $this->mapRow($model);
                $row = $this->convertRow($data);
                $this->validateRow($row);

                $writer->insertOne(array_values($row));
            }
        });
        Storage::disk($this->disk)->putFileAs($this->folderPath, new File($tempDirectory->path($this->filename)), $this->filename);
        $tempDirectory->delete();
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
     */
    private function validateRow(array $row): void
    {
        if (array_keys($row) !== static::HEADER) {
            throw new \LogicException('Invalid row');
        }
    }
}
