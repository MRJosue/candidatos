<?php

namespace App\Services;

use App\Models\CvProfile;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CvTranslationService
{
    public function translate(CvProfile $profile, string $targetLanguage): array
    {
        $apiKey = config('services.gemini.key');

        if (! filled($apiKey)) {
            throw new RuntimeException('Configura GEMINI_API_KEY para traducir el CV con IA.');
        }

        $source = $this->sourcePayload($profile);
        $targetLabel = CvProfile::languageOptions()[$targetLanguage] ?? $targetLanguage;
        $lastException = null;

        foreach ($this->models() as $model) {
            $response = Http::withHeaders([
                'X-goog-api-key' => $apiKey,
            ])
                ->acceptJson()
                ->timeout(45)
                ->retry(2, 1500, throw: false)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'systemInstruction' => [
                        'parts' => [[
                            'text' => 'Traduce CVs y responde solo JSON valido segun el esquema. No inventes informacion.',
                        ]],
                    ],
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => implode("\n", [
                                "Traduce este CV a {$targetLabel}.",
                                'Conserva nombres propios, empresas, universidades, URLs, emails, telefonos, software, tecnologias, frameworks y certificaciones oficiales.',
                                'Adapta cargos, resumen, responsabilidades, categorias, habilidades blandas y etiquetas naturales del CV al idioma destino.',
                                'Devuelve la misma estructura JSON, con todos los campos presentes. Si un campo no tiene informacion real, devuelvelo como cadena vacia, nunca como texto "null".',
                                json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            ]),
                        ]],
                    ]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->schema(),
                    ],
                ]);

            try {
                $response->throw();
            } catch (RequestException $exception) {
                $lastException = $exception;

                if ($this->shouldTryNextModel($response->status())) {
                    continue;
                }

                throw new RuntimeException($this->requestErrorMessage($exception), previous: $exception);
            }

            $decoded = json_decode($this->responseText($response->json()), true);

            if (! is_array($decoded)) {
                throw new RuntimeException('Gemini no devolvio JSON valido para traducir el CV.');
            }

            return $this->normalize($decoded);
        }

        if ($lastException instanceof RequestException) {
            throw new RuntimeException($this->requestErrorMessage($lastException), previous: $lastException);
        }

        throw new RuntimeException('Gemini no pudo traducir el CV. Revisa la API key, cuota o intenta de nuevo.');
    }

    private function sourcePayload(CvProfile $profile): array
    {
        $profile->loadMissing(['experiences', 'education', 'skills']);

        return [
            'profile' => [
                'title' => $profile->title,
                'full_name' => $profile->full_name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'location' => $profile->location,
                'headline' => $profile->headline,
                'tagline' => $profile->tagline,
                'summary' => $profile->summary,
                'objective' => $profile->objective,
                'skills_section_title' => $profile->skills_section_title,
                'soft_skills_section_title' => $profile->soft_skills_section_title,
                'awards' => $profile->awards,
                'leadership_activities' => $profile->leadership_activities,
                'interests' => $profile->interests,
                'linkedin_url' => $profile->linkedin_url,
                'portfolio_url' => $profile->portfolio_url,
            ],
            'experiences' => $profile->experiences->map(fn ($experience) => [
                'position' => $experience->position,
                'company' => $experience->company,
                'location' => $experience->location,
                'description' => $experience->description,
                'tools_used' => $experience->tools_used,
            ])->values()->all(),
            'education' => $profile->education->map(fn ($education) => [
                'institution' => $education->institution,
                'location' => $education->location,
                'degree' => $education->degree,
                'field' => $education->field,
                'gpa' => $education->gpa,
                'honors' => $education->honors,
                'thesis' => $education->thesis,
                'relevant_coursework' => $education->relevant_coursework,
                'description' => $education->description,
            ])->values()->all(),
            'skills' => $profile->skills->map(fn ($skill) => [
                'name' => $skill->name,
                'category' => $skill->category,
                'type' => $skill->type ?: 'skill',
            ])->values()->all(),
        ];
    }

    private function responseText(array $payload): string
    {
        foreach (($payload['candidates'] ?? []) as $candidate) {
            foreach (data_get($candidate, 'content.parts', []) as $content) {
                if (is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function models(): array
    {
        return collect([
            config('services.gemini.cv_import_model', 'gemini-2.5-flash'),
            ...config('services.gemini.cv_import_fallback_models', []),
        ])
            ->map(fn ($model) => trim((string) $model))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function shouldTryNextModel(int $status): bool
    {
        return in_array($status, [429, 500, 502, 503, 504], true);
    }

    private function requestErrorMessage(RequestException $exception): string
    {
        $response = $exception->response;
        $status = $response?->status();
        $googleStatus = $response?->json('error.status');
        $googleMessage = $response?->json('error.message');

        if (is_string($googleMessage) && $googleMessage !== '') {
            $summary = Str::of($googleMessage)->before("\n")->limit(240);
            $details = collect([$status, $googleStatus])->filter()->implode(' ');

            return trim("Gemini no pudo traducir el CV ({$details}): {$summary}");
        }

        return 'Gemini no pudo traducir el CV. Revisa la API key, cuota o intenta de nuevo.';
    }

    private function normalize(array $data): array
    {
        return [
            'profile' => collect($data['profile'] ?? [])
                ->map(fn ($value) => $this->cleanText($value))
                ->all(),
            'experiences' => collect($data['experiences'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($item) => [
                    'position' => $this->cleanText($item['position'] ?? null),
                    'company' => $this->cleanText($item['company'] ?? null),
                    'location' => $this->cleanText($item['location'] ?? null),
                    'description' => $this->cleanText($item['description'] ?? null),
                    'tools_used' => $this->cleanText($item['tools_used'] ?? null),
                ])
                ->values()
                ->all(),
            'education' => collect($data['education'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($item) => collect([
                    'institution' => $item['institution'] ?? '',
                    'location' => $item['location'] ?? '',
                    'degree' => $item['degree'] ?? '',
                    'field' => $item['field'] ?? '',
                    'gpa' => $item['gpa'] ?? '',
                    'honors' => $item['honors'] ?? '',
                    'thesis' => $item['thesis'] ?? '',
                    'relevant_coursework' => $item['relevant_coursework'] ?? '',
                    'description' => $item['description'] ?? '',
                ])->map(fn ($value) => $this->cleanText($value))->all())
                ->values()
                ->all(),
            'skills' => collect($data['skills'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($item) => [
                    'name' => $this->cleanText($item['name'] ?? null),
                    'category' => $this->cleanText($item['category'] ?? null),
                    'type' => in_array($item['type'] ?? 'skill', ['software', 'skill', 'language', 'soft_skill'], true) ? $item['type'] : 'skill',
                ])
                ->values()
                ->all(),
        ];
    }

    private function cleanText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^(?:null|n\/a|na|none|-|--)$/iu', $value) ? null : $value;
    }

    private function schema(): array
    {
        $stringFields = fn (array $fields) => collect($fields)
            ->mapWithKeys(fn ($field) => [$field => ['type' => 'string']])
            ->all();

        $profileFields = [
            'title',
            'full_name',
            'email',
            'phone',
            'location',
            'headline',
            'tagline',
            'summary',
            'objective',
            'skills_section_title',
            'soft_skills_section_title',
            'awards',
            'leadership_activities',
            'interests',
            'linkedin_url',
            'portfolio_url',
        ];
        $experienceFields = ['position', 'company', 'location', 'description', 'tools_used'];
        $educationFields = ['institution', 'location', 'degree', 'field', 'gpa', 'honors', 'thesis', 'relevant_coursework', 'description'];

        return [
            'type' => 'object',
            'required' => ['profile', 'experiences', 'education', 'skills'],
            'properties' => [
                'profile' => [
                    'type' => 'object',
                    'required' => $profileFields,
                    'properties' => $stringFields($profileFields),
                ],
                'experiences' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => $experienceFields,
                        'properties' => $stringFields($experienceFields),
                    ],
                ],
                'education' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => $educationFields,
                        'properties' => $stringFields($educationFields),
                    ],
                ],
                'skills' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['name', 'category', 'type'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'category' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
