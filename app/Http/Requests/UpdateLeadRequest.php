<?php

namespace App\Http\Requests;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    /**
     * assigned_to is intentionally not accepted here — reassignment must go through
     * POST /leads/{lead}/assign, which is manager-only and checks the rep role.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'digits:10'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['sometimes', Rule::enum(LeadSource::class)],
            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'expected_value' => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Email must be a valid email address.',
            'phone.digits' => 'Phone must contain exactly 10 digits.',
            'expected_value.numeric' => 'Expected value must be a number.',
        ];
    }
}
