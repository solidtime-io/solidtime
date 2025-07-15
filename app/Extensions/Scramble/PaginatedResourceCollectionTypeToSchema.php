<?php

declare(strict_types=1);

namespace App\Extensions\Scramble;

use App\Http\Resources\PaginatedResourceCollection;
use App\Http\Resources\V1\TimeEntry\TimeEntryCollection;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class PaginatedResourceCollectionTypeToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(PaginatedResourceCollection::class);
    }

    public function toSchema(Type $type): ?OpenApiObjectType
    {
        /** @var Type|null $collectingClassType */
        $collectingClassType = $type->templateTypes[0] ?? null;

        if (! $collectingClassType instanceof ObjectType) {
            return null;
        }

        if (! $collectingClassType->isInstanceOf(JsonResource::class) && ! $collectingClassType->isInstanceOf(Model::class)) {
            return null;
        }

        $collectingType = $this->openApiTransformer->transform($collectingClassType);

        $newType = new OpenApiObjectType;
        $newType->addProperty('data', (new ArrayType)->setItems($collectingType));
        if ($type instanceof ObjectType && $type->isInstanceOf(TimeEntryCollection::class)) {
            $newType->addProperty(
                'meta',
                (new OpenApiObjectType)
                    ->addProperty('total', (new IntegerType)->setDescription('Total number of items being paginated.'))
                    ->setRequired(['total'])
            );
            $newType->setRequired(['data', 'meta']);
        } else {
            $newType->addProperty(
                'links',
                (new OpenApiObjectType)
                    ->addProperty('first', (new StringType)->nullable(true))
                    ->addProperty('last', (new StringType)->nullable(true))
                    ->addProperty('prev', (new StringType)->nullable(true))
                    ->addProperty('next', (new StringType)->nullable(true))
                    ->setRequired(['first', 'last', 'prev', 'next'])
            );
            $newType->addProperty(
                'meta',
                (new OpenApiObjectType)
                    ->addProperty('current_page', new IntegerType)
                    ->addProperty('from', (new IntegerType)->nullable(true))
                    ->addProperty('last_page', new IntegerType)
                    ->addProperty('links', (new ArrayType)->setItems(
                        (new OpenApiObjectType)
                            ->addProperty('url', (new StringType)->nullable(true))
                            ->addProperty('label', new StringType)
                            ->addProperty('active', new BooleanType)
                            ->setRequired(['url', 'label', 'active'])
                    )->setDescription('Generated paginator links.'))
                    ->addProperty('path', (new StringType)->nullable(true)->setDescription('Base path for paginator generated URLs.'))
                    ->addProperty('per_page', (new IntegerType)->setDescription('Number of items shown per page.'))
                    ->addProperty('to', (new IntegerType)->nullable(true)->setDescription('Number of the last item in the slice.'))
                    ->addProperty('total', (new IntegerType)->setDescription('Total number of items being paginated.'))
                    ->setRequired(['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'])
            );
            $newType->setRequired(['data', 'links', 'meta']);
        }

        return $newType;
    }

    /**
     * @param  Generic  $type
     */
    public function toResponse(Type $type): ?Response
    {
        /** @var ObjectType|null $collectingClassType */
        $collectingClassType = $type->templateTypes[0] ?? null;
        if (! $collectingClassType instanceof ObjectType) {
            return null;
        }
        $type = $this->toSchema($type);

        return Response::make(200)
            ->description('Paginated set of `'.$this->components->uniqueSchemaName($collectingClassType->name).'`')
            ->setContent('application/json', Schema::fromType($type));
    }
}
