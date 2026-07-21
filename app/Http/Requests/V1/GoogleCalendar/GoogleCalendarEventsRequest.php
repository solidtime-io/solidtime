<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\GoogleCalendar;

use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class GoogleCalendarEventsRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|\Closure>>
     */
    public function rules(): array
    {
        return [
            'start' => [
                'required',
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
            ],
            'end' => [
                'required',
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
                'after:start',
            ],
        ];
    }

    public function getStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->input('start'), 'UTC');
    }

    public function getEnd(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $this->input('end'), 'UTC');
    }
}
