<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCvEducationRequest extends FormRequest
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
            'institution' => ['required', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:160'],
            'degree' => ['required', 'string', 'max:180'],
            'field' => ['nullable', 'string', 'max:180'],
            'gpa' => ['nullable', 'string', 'max:40'],
            'honors' => ['nullable', 'string', 'max:180'],
            'thesis' => ['nullable', 'string', 'max:1000'],
            'relevant_coursework' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                Rule::when($this->filled('start_date'), ['after_or_equal:start_date']),
            ],
            'description' => ['nullable', 'string', 'max:1500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'institution' => 'institución',
            'location' => 'ubicación',
            'degree' => 'título o grado',
            'field' => 'área',
            'gpa' => 'promedio',
            'honors' => 'honores',
            'thesis' => 'tesis',
            'relevant_coursework' => 'cursos relevantes',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
            'description' => 'notas',
            'sort_order' => 'orden',
        ];
    }
}
