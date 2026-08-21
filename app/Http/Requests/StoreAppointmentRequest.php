<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'talent_id' => [
                'required',
                Rule::exists('talents', 'id')
                    ->where(fn ($query) => $query->whereIn('recruiter_id', $this->user()->visibleRecruiterUserIds())),
            ],
            'vacancy_id' => [
                'required',
                Rule::exists('vacancies', 'id')
                    ->where(fn ($query) => $query->whereIn('recruiter_id', $this->user()->visibleRecruiterUserIds())),
            ],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'timezone' => ['nullable', 'timezone'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
