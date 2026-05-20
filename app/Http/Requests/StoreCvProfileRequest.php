<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCvProfileRequest extends FormRequest
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
            'cv_template_id' => ['nullable', 'exists:cv_templates,id'],
            'title' => ['required', 'string', 'max:120'],
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:160'],
            'headline' => ['nullable', 'string', 'max:180'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'skills_section_title' => ['nullable', 'string', 'max:120'],
            'soft_skills_section_title' => ['nullable', 'string', 'max:120'],
            'awards' => ['nullable', 'string', 'max:2000'],
            'leadership_activities' => ['nullable', 'string', 'max:2000'],
            'interests' => ['nullable', 'string', 'max:1000'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
