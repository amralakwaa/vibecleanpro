<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for both public lead-capture forms (Quote and
 * Contact) - same person-facing fields, same guardrails. The honeypot
 * field is deliberately NOT validated here - see QuoteController /
 * ContactController, which check it before validation even runs and
 * silently pretend success instead of surfacing a validation error (a
 * real visitor never sees that field, so a filled one is always a bot;
 * telling it "this field must be empty" only helps it adapt).
 */
class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'phone.required' => 'رقم الجوال مطلوب.',
            'phone.regex' => 'صيغة رقم الجوال غير صحيحة.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'message.max' => 'الرسالة طويلة جدًا.',
        ];
    }
}
