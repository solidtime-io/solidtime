<?php

declare(strict_types=1);

namespace App\Extensions\Scramble;

use App\Exceptions\Api\ApiException;
use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Support\Str;

class ApiExceptionTypeToSchema extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(ApiException::class);
    }

    public function toResponse(Type $type): Response
    {
        $validationResponseBodyType = (new OpenApiTypes\ObjectType)
            ->addProperty(
                'error',
                (new OpenApiTypes\BooleanType)
                    ->setDescription('Whether the response is an error.')
            )
            ->addProperty(
                'key',
                (new OpenApiTypes\StringType)
                    ->setDescription('Error key.')
            )
            ->addProperty(
                'message',
                (new OpenApiTypes\StringType)
                    ->setDescription('Error message.')
            )
            ->setRequired(['error', 'key', 'message']);

        return Response::make(400)
            ->description('API exception')
            ->setContent(
                'application/json',
                Schema::fromType($validationResponseBodyType)
            );
    }

    public function reference(ObjectType $type): Reference
    {
        return new Reference('responses', Str::start($type->name, '\\'), $this->components);
    }
}
