<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Activity::class, $this->route('lead')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'body' => ['required', 'string'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Please select an activity type.',
            'body.required' => 'Please enter the activity details.',
            'occurred_at.date' => 'Please enter a valid date and time.',
        ];
    }
}
