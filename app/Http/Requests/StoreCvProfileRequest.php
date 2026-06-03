<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCvProfileRequest extends FormRequest
{
    public const LARGE_TEXT_MAX = 12000;

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
            'cv_template_id' => [
                'nullable',
                Rule::exists('cv_templates', 'id')->where('is_active', true),
            ],
            'language' => ['nullable', Rule::in(['es', 'en'])],
            'title' => ['required', 'string', 'max:120'],
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:160'],
            'headline' => ['nullable', 'string', 'max:180'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:'.self::LARGE_TEXT_MAX],
            'objective' => ['nullable', 'string', 'max:'.self::LARGE_TEXT_MAX],
            'skills_section_title' => ['nullable', 'string', 'max:120'],
            'soft_skills_section_title' => ['nullable', 'string', 'max:120'],
            'awards' => ['nullable', 'string', 'max:'.self::LARGE_TEXT_MAX],
            'leadership_activities' => ['nullable', 'string', 'max:'.self::LARGE_TEXT_MAX],
            'interests' => ['nullable', 'string', 'max:'.self::LARGE_TEXT_MAX],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max.string' => 'El campo :attribute es demasiado largo. Reduce un poco el texto o divídelo en secciones.',
            'summary.max' => 'El campo resumen profesional es demasiado largo. Reduce un poco el texto o divídelo en secciones.',
            'objective.max' => 'El campo objetivo profesional es demasiado largo. Reduce un poco el texto o divídelo en secciones.',
            'awards.max' => 'El campo certificaciones es demasiado largo. Reduce un poco el texto o divídelo en secciones.',
            'leadership_activities.max' => 'El campo actividades de liderazgo es demasiado largo. Reduce un poco el texto o divídelo en secciones.',
            'interests.max' => 'El campo intereses es demasiado largo. Reduce un poco el texto o divídelo en secciones.',
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'El campo :attribute debe ser un correo válido.',
            'url' => 'El campo :attribute debe ser una URL válida.',
            'in' => 'El valor seleccionado para :attribute no es válido.',
            'boolean' => 'El valor de :attribute no es válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cv_template_id' => 'plantilla',
            'language' => 'idioma',
            'title' => 'título del CV',
            'full_name' => 'nombre completo',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'location' => 'ubicación',
            'headline' => 'titular profesional',
            'tagline' => 'subtítulo',
            'summary' => 'resumen profesional',
            'objective' => 'objetivo profesional',
            'skills_section_title' => 'título de habilidades',
            'soft_skills_section_title' => 'título de habilidades blandas',
            'awards' => 'certificaciones',
            'leadership_activities' => 'actividades de liderazgo',
            'interests' => 'intereses',
            'linkedin_url' => 'URL de LinkedIn',
            'portfolio_url' => 'URL de portafolio',
            'is_primary' => 'CV principal',
        ];
    }
}
