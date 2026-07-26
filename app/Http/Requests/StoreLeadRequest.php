<?php

namespace App\Http\Requests;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lead::class);
    }

    /**
     * assigned_to is intentionally not accepted here — a lead is created unassigned,
     * and reassignment must go through POST /leads/{lead}/assign (manager-only, rep role checked).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'digits:10'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::enum(LeadSource::class)],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'expected_value' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the lead name.',
            'email.required' => 'Please enter the lead email address.',
            'email.email' => 'Email must be a valid email address.',
            'phone.required' => 'Please enter the lead phone number.',
            'phone.digits' => 'Phone must contain exactly 10 digits.',
            'source.required' => 'Please select a lead source.',
            'expected_value.required' => 'Please enter the expected value.',
            'expected_value.numeric' => 'Expected value must be a number.',
        ];
    }
}
