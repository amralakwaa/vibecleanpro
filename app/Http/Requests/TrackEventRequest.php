<?php

namespace App\Http\Requests;

use App\Enums\ConversionEventType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TrackEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_map(fn (ConversionEventType $type) => $type->value, ConversionEventType::browserReportable()))],
            'path' => ['required', 'string', 'max:255', 'starts_with:/'],
        ];
    }

    /**
     * A tracking beacon never gets an error body: the browser ignores the
     * response anyway, and a uniform 204 tells a prober nothing about what
     * was accepted.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->noContent());
    }

    public function eventType(): ConversionEventType
    {
        return ConversionEventType::from($this->validated('type'));
    }
}
