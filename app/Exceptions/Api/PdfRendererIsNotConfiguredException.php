<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class PdfRendererIsNotConfiguredException extends ApiException
{
    public const string KEY = 'pdf_renderer_is_not_configured';
}
