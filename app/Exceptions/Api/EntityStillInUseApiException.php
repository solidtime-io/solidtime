<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class EntityStillInUseApiException extends ApiException
{
    private string $modelToDelete;

    private string $modelInUse;

    public function __construct(string $modelToDelete, string $modelInUse)
    {
        parent::__construct('', 0, null);
        $this->modelToDelete = $modelToDelete;
        $this->modelInUse = $modelInUse;
    }

    public const string KEY = 'entity_still_in_use';

    /**
     * Get the translated message for the exception.
     */
    #[\Override]
    public function getTranslatedMessage(): string
    {
        return __('exceptions.api.'.$this->getKey(), [
            'modelToDelete' => __('validation.entities.'.$this->modelToDelete),
            'modelInUse' => __('validation.entities.'.$this->modelInUse),
        ]);
    }
}
