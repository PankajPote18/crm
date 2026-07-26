<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', Lead::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Rep->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Please select a rep to assign this lead to.',
            'assigned_to.exists' => 'The selected user is not a valid rep.',
        ];
    }
}
