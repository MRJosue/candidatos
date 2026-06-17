<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CvAiDocumentImportService
{
    private const ANALYSIS_QUALITY_THRESHOLD = 30;

    public function analyze(string $text): array
    {
        $apiKey = config('services.gemini.key');

        if (! filled($apiKey)) {
            throw new RuntimeException('Configura GEMINI_API_KEY para analizar el CV con IA.');
        }

        $models = $this->models();
        $lastException = null;
        $bestCandidate = null;
        $bestScore = 0;

        foreach ($models as $model) {
            try {
                $direct = $this->analyzeStructuredText($apiKey, $model, $text);
                $directScore = $this->analysisQualityScore($direct);

                if ($directScore >= self::ANALYSIS_QUALITY_THRESHOLD) {
                    return $direct;
                }

                if ($directScore > $bestScore) {
                    $bestScore = $directScore;
                    $bestCandidate = $direct;
                }

                $orderedText = $this->reorderText($apiKey, $model, $text);

                if ($orderedText === '') {
                    continue;
                }

                $ordered = $this->analyzeStructuredText($apiKey, $model, $orderedText, ordered: true);
                $orderedScore = $this->analysisQualityScore($ordered);

                if ($orderedScore >= self::ANALYSIS_QUALITY_THRESHOLD) {
                    return $ordered;
                }

                if ($orderedScore > $bestScore) {
                    $bestScore = $orderedScore;
                    $bestCandidate = $ordered;
                }
            } catch (RequestException $exception) {
                $lastException = $exception;

                if ($this->shouldTryNextModel($exception->response?->status() ?? 0)) {
                    continue;
                }

                throw new RuntimeException($this->requestErrorMessage($exception), previous: $exception);
            }
        }

        if ($bestCandidate !== null) {
            return $bestCandidate;
        }

        if ($lastException instanceof RequestException) {
            throw new RuntimeException($this->requestErrorMessage($lastException), previous: $lastException);
        }

        throw new RuntimeException('Gemini no pudo analizar el documento. Revisa la API key, cuota o intenta de nuevo.');
    }

    /**
     * @throws RequestException
     */
    private function analyzeStructuredText(string $apiKey, string $model, string $text, bool $ordered = false): array
    {
        $response = $this->request(
            $apiKey,
            $model,
            [
                'systemInstruction' => [
                    'parts' => [[
                        'text' => 'Extrae datos de CV y responde solo JSON valido segun el esquema. Si falta un dato usa cadena vacia o lista vacia.',
                    ]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [[
                            'text' => $ordered
                                ? "Analiza este CV ya reordenado por secciones y devuelve JSON estructurado. Conserva separacion estricta entre software y skills. No devuelvas habilidades blandas. En experiences separa responsabilidades en description y herramientas/plataformas especificas de ese puesto en tools_used. En awards incluye solo certificaciones, cursos profesionales y diplomados detectados, uno por elemento:\n\n".Str::limit($text, 24000, '')
                                : "Analiza este CV y devuelve JSON estructurado. Separa software de habilidades: software debe contener herramientas, aplicaciones, plataformas, sistemas operativos, IDEs, suites y productos usados; skills debe contener lenguajes de programacion, frameworks, bases de datos, metodologias y capacidades tecnicas. No extraigas ni devuelvas habilidades blandas. En experiences separa responsabilidades en description y herramientas/plataformas especificas de ese puesto en tools_used. En awards incluye solo certificaciones, cursos profesionales y diplomados detectados, uno por elemento. No incluyas reconocimientos generales, premios, hobbies ni logros laborales en awards:\n\n".Str::limit($text, 24000, ''),
                        ]],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $this->schema(),
                ],
            ],
        );

        $decoded = json_decode($this->responseText($response->json()), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini no devolvio JSON valido para previsualizar.');
        }

        return $this->normalize($decoded);
    }

    /**
     * @throws RequestException
     */
    private function reorderText(string $apiKey, string $model, string $text): string
    {
        $response = $this->request(
            $apiKey,
            $model,
            [
                'systemInstruction' => [
                    'parts' => [[
                        'text' => 'Reordena CVs desestructurados sin inventar datos. Devuelve solo texto plano legible.',
                    ]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [[
                            'text' => "Ordena este CV en texto plano con secciones claras para facilitar su extracción posterior. Usa encabezados exactos cuando existan datos: PROFILE, SUMMARY, EXPERIENCE, EDUCATION, SOFTWARE, SKILLS, LANGUAGES, AWARDS. Dentro de EXPERIENCE crea bloques consistentes con Company, Position, Period, Description y Tools cuando existan. No inventes nada, no traduzcas, no resumas y conserva los datos originales aunque el formato venga roto:\n\n".Str::limit($text, 24000, ''),
                        ]],
                    ],
                ],
            ],
        );

        return trim($this->responseText($response->json()));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @throws RequestException
     */
    private function request(string $apiKey, string $model, array $payload): \Illuminate\Http\Client\Response
    {
        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
        ])
            ->acceptJson()
            ->timeout(45)
            ->retry(2, 1500, throw: false)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);

        $response->throw();

        return $response;
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
     */
    private function analysisQualityScore(array $data): int
    {
        $profile = (array) ($data['profile'] ?? []);
        $score = 0;
        $score += $this->filledStringScore($profile['full_name'] ?? null, 8);
        $score += $this->filledStringScore($profile['headline'] ?? null, 8);
        $score += $this->filledStringScore($profile['summary'] ?? null, 12);
        $score += $this->filledStringScore($profile['email'] ?? null, 6);
        $score += $this->filledStringScore($profile['phone'] ?? null, 4);
        $score += min(count($data['software'] ?? []), 12);
        $score += min(count($data['skills'] ?? []), 12);
        $score += min(count($data['languages'] ?? []), 6);
        $score += min(count($data['education'] ?? []), 10) * 2;

        foreach ((array) ($data['experiences'] ?? []) as $experience) {
            if (! is_array($experience)) {
                continue;
            }

            $score += $this->filledStringScore($experience['position'] ?? null, 8);
            $score += $this->filledStringScore($experience['company'] ?? null, 8);
            $score += $this->filledStringScore($experience['period'] ?? null, 6);
            $score += $this->filledStringScore($experience['description'] ?? null, 4);
        }

        return $score;
    }

    private function filledStringScore(mixed $value, int $points): int
    {
        return is_string($value) && trim($value) !== '' ? $points : 0;
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
