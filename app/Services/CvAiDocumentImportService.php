<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CvAiDocumentImportService
{
    public function analyze(string $text): array
    {
        $apiKey = config('services.gemini.key');

        if (! filled($apiKey)) {
            throw new RuntimeException('Configura GEMINI_API_KEY para analizar el CV con IA.');
        }

        $models = $this->models();
        $lastException = null;

        foreach ($models as $model) {
            $response = Http::withHeaders([
                'X-goog-api-key' => $apiKey,
            ])
                ->acceptJson()
                ->timeout(45)
                ->retry(2, 1500, throw: false)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'systemInstruction' => [
                        'parts' => [[
                            'text' => 'Extrae datos de CV y responde solo JSON valido segun el esquema. Si falta un dato usa cadena vacia o lista vacia.',
                        ]],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [[
                                'text' => "Analiza este CV y devuelve JSON estructurado. Separa software de habilidades: software debe contener herramientas, aplicaciones, plataformas, sistemas operativos, IDEs, suites y productos usados; skills debe contener lenguajes de programacion, frameworks, bases de datos, metodologias y capacidades tecnicas. No extraigas ni devuelvas habilidades blandas. En experiences separa responsabilidades en description y herramientas/plataformas especificas de ese puesto en tools_used. En awards incluye solo certificaciones, cursos profesionales y diplomados detectados, uno por elemento. No incluyas reconocimientos generales, premios, hobbies ni logros laborales en awards:\n\n".Str::limit($text, 24000, ''),
                            ]],
                        ],
                    ],
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
                throw new RuntimeException('Gemini no devolvio JSON valido para previsualizar.');
            }

            return $this->normalize($decoded);
        }

        if ($lastException instanceof RequestException) {
            throw new RuntimeException($this->requestErrorMessage($lastException), previous: $lastException);
        }

        throw new RuntimeException('Gemini no pudo analizar el documento. Revisa la API key, cuota o intenta de nuevo.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
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

    private function requestErrorMessage(RequestException $exception): string
    {
        $response = $exception->response;
        $status = $response?->status();
        $googleStatus = $response?->json('error.status');
        $googleMessage = $response?->json('error.message');

        if (is_string($googleMessage) && $googleMessage !== '') {
            $summary = Str::of($googleMessage)->before("\n")->limit(240);
            $details = collect([$status, $googleStatus])
                ->filter()
                ->implode(' ');

            return trim("Gemini no pudo analizar el documento ({$details}): {$summary}");
        }

        return 'Gemini no pudo analizar el documento. Revisa la API key, cuota o intenta de nuevo.';
    }

    private function shouldTryNextModel(int $status): bool
    {
        return in_array($status, [429, 500, 502, 503, 504], true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $profile = Arr::only((array) ($data['profile'] ?? []), [
            'full_name',
            'email',
            'phone',
            'location',
            'headline',
            'summary',
            'linkedin_url',
            'portfolio_url',
        ]);

        return [
            'profile' => collect($profile)
                ->map(fn ($value) => is_string($value) ? trim($value) : null)
                ->all(),
            'experiences' => collect($data['experiences'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($item) => [
                    'position' => trim((string) ($item['position'] ?? '')),
                    'company' => trim((string) ($item['company'] ?? '')),
                    'period' => trim((string) ($item['period'] ?? '')),
                    'description' => trim((string) ($item['description'] ?? '')),
                    'tools_used' => trim((string) ($item['tools_used'] ?? '')),
                ])
                ->values()
                ->all(),
            'education' => collect($data['education'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($item) => [
                    'degree' => trim((string) ($item['degree'] ?? '')),
                    'institution' => trim((string) ($item['institution'] ?? '')),
                    'period' => trim((string) ($item['period'] ?? '')),
                    'description' => trim((string) ($item['description'] ?? '')),
                ])
                ->values()
                ->all(),
            'software' => $this->stringList($data['software'] ?? []),
            'skills' => $this->stringList($data['skills'] ?? []),
            'languages' => $this->stringList($data['languages'] ?? []),
            'awards' => $this->stringList($data['awards'] ?? []),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $profileProperties = collect([
            'full_name',
            'email',
            'phone',
            'location',
            'headline',
            'summary',
            'linkedin_url',
            'portfolio_url',
        ])->mapWithKeys(fn ($field) => [$field => ['type' => 'string']])->all();

        return [
            'type' => 'object',
            'required' => ['profile', 'experiences', 'education', 'software', 'skills', 'languages', 'awards'],
            'properties' => [
                'profile' => [
                    'type' => 'object',
                    'required' => array_keys($profileProperties),
                    'properties' => $profileProperties,
                ],
                'experiences' => [
                    'type' => 'array',
                    'items' => $this->entrySchema(['position', 'company', 'period', 'description', 'tools_used']),
                ],
                'education' => [
                    'type' => 'array',
                    'items' => $this->entrySchema(['degree', 'institution', 'period', 'description']),
                ],
                'software' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'skills' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'languages' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'awards' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function entrySchema(array $fields): array
    {
        return [
            'type' => 'object',
            'required' => $fields,
            'properties' => collect($fields)->mapWithKeys(fn ($field) => [$field => ['type' => 'string']])->all(),
        ];
    }
}
