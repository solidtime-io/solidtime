<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class FeatureIsNotAvailableInFreePlanApiException extends ApiException
{
    public const string KEY = 'feature_is_not_available_in_free_plan';
}
