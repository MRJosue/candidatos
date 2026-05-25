<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class CvDocumentImportService
{
    public function import(UploadedFile $file): array
    {
        $text = $this->extractText($file);

        $software = $this->itemsFromSection($sections['software'] ?? '');
        $skills = $this->itemsFromSection($sections['skills'] ?? '');

        if ($software === [] && $skills !== []) {
            [$software, $skills] = $this->partitionSoftwareItems($skills);
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'parsed' => $this->parseText($text),
        ];
    }

    public function extractText(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $this->readableUploadPath($file);

        return match ($extension) {
            'txt' => $this->extractTxtText($path),
            'docx' => $this->extractDocxText($path),
            'pdf' => $this->extractPdfText($path),
            default => throw new RuntimeException('Formato de CV no soportado.'),
        };
    }

    public function parseText(string $text): array
    {
        $cleanText = $this->normalizeText($text);
        $lines = collect(preg_split('/\R/u', $cleanText) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $sections = $this->sections($lines->all());
        $summary = Arr::first([
            $sections['summary'] ?? null,
            $sections['profile'] ?? null,
            $sections['objective'] ?? null,
        ]);
        $experiences = $this->entriesFromSection($sections['experience'] ?? '');
        if ($experiences === []) {
            $experiences = $this->experienceEntriesFromLines($lines->all());
        }

        return [
            'profile' => [
                'full_name' => $this->guessName($lines->all()),
                'email' => $this->firstMatch('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $cleanText),
                'phone' => $this->firstMatch('/(?:\+?\d[\d\s().-]{7,}\d)/u', $lines->take(12)->implode("\n")),
                'headline' => $lines->get(1),
                'summary' => $summary,
                'linkedin_url' => $this->firstLink($cleanText, 'linkedin.com'),
                'portfolio_url' => $this->firstPortfolioLink($cleanText),
            ],
            'software' => $software,
            'skills' => $skills,
            'languages' => $this->itemsFromSection($sections['languages'] ?? ''),
            'experiences' => $experiences,
            'education' => $this->entriesFromSection($sections['education'] ?? ''),
            'raw_text' => Str::limit($cleanText, 12000, ''),
        ];
    }

    private function readableUploadPath(UploadedFile $file): string
    {
        $path = $file->getRealPath() ?: $file->getPathname();

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            throw new RuntimeException('No se pudo leer el archivo subido. Revisa permisos de storage y el directorio temporal de PHP.');
        }

        return $path;
    }

    private function extractTxtText(string $path): string
    {
        $text = file_get_contents($path);

        if ($text === false) {
            throw new RuntimeException('No se pudo leer el TXT subido.');
        }

        return $this->normalizeText($text);
    }

    private function extractDocxText(string $path): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('El servidor no tiene habilitada la extension PHP zip, necesaria para leer DOCX.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el DOCX.');
        }

        $document = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $document) {
            throw new RuntimeException('El DOCX no contiene texto legible.');
        }

        $document = preg_replace('/<\/w:p>/u', "\n", $document) ?? $document;
        $document = preg_replace('/<\/w:tr>/u', "\n", $document) ?? $document;
        $text = html_entity_decode(strip_tags($document), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->normalizeText($text);
    }

    private function extractPdfText(string $path): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($path);
        $text = $this->normalizeText($pdf->getText());

        if (mb_strlen($text) < 40) {
            throw new RuntimeException('El PDF no tiene texto suficiente. Puede ser escaneado o estar como imagen.');
        }

        return $text;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<string, string>
     */
    private function sections(array $lines): array
    {
        $aliases = [
            'summary' => ['resumen', 'extracto', 'sobre mi', 'acerca de mi', 'perfil profesional', 'professional summary', 'summary'],
            'profile' => ['perfil', 'profile'],
            'objective' => ['objetivo', 'objective'],
            'experience' => ['experiencia', 'experiencia laboral', 'experiencia profesional', 'work experience', 'professional experience', 'employment'],
            'education' => ['educacion', 'educación', 'formacion', 'formación', 'formacion academica', 'academic background', 'education'],
            'software' => ['software', 'herramientas', 'herramientas digitales', 'tools', 'platforms', 'plataformas', 'aplicaciones'],
            'skills' => ['habilidades', 'competencias', 'skills', 'technical skills', 'tecnologias', 'tecnologías', 'lenguajes', 'programming languages'],
            'languages' => ['idiomas', 'languages'],
        ];

        $lookup = [];
        foreach ($aliases as $section => $headings) {
            foreach ($headings as $heading) {
                $lookup[$this->headingKey($heading)] = $section;
            }
        }

        $current = null;
        $sections = [];

        foreach ($lines as $line) {
            $key = $this->headingKey($line);

            if (isset($lookup[$key])) {
                $current = $lookup[$key];
                $sections[$current] ??= [];

                continue;
            }

            if ($current) {
                $sections[$current][] = $line;
            }
        }

        return collect($sections)
            ->map(fn ($sectionLines) => trim(implode("\n", $sectionLines)))
            ->all();
    }

    private function headingKey(string $value): string
    {
        $value = Str::ascii(Str::lower(trim($value)));
        $value = preg_replace('/[^a-z ]/u', '', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function guessName(array $lines): ?string
    {
        foreach (array_slice($lines, 0, 8) as $line) {
            if (str_contains($line, '@') || preg_match('/\d{4,}/', $line)) {
                continue;
            }

            if (mb_strlen($line) >= 4 && mb_strlen($line) <= 90) {
                return $line;
            }
        }

        return null;
    }

    private function firstMatch(string $pattern, string $text): ?string
    {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[0]);
        }

        return null;
    }

    private function firstLink(string $text, string $needle): ?string
    {
        return collect($this->links($text))
            ->first(fn ($link) => str_contains(Str::lower($link), Str::lower($needle)));
    }

    private function firstPortfolioLink(string $text): ?string
    {
        return collect($this->links($text))
            ->first(fn ($link) => ! str_contains(Str::lower($link), 'linkedin.com'));
    }

    /**
     * @return array<int, string>
     */
    private function links(string $text): array
    {
        preg_match_all('/https?:\/\/[^\s]+|(?:linkedin|github)\.com\/[^\s]+/iu', $text, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($item) => trim($item, " \t\n\r\0\x0B.,;"))
            ->map(fn ($item) => str_starts_with($item, 'http') ? $item : 'https://'.$item)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function itemsFromSection(string $section): array
    {
        $parts = preg_split('/[\n,;|]+/u', $section) ?: [];

        return collect($parts)
            ->map(fn ($item) => trim(preg_replace('/^[\-*•]\s*/u', '', $item) ?? $item))
            ->filter(fn ($item) => mb_strlen($item) >= 2)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $items
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function partitionSoftwareItems(array $items): array
    {
        $groups = collect($items)->partition(fn ($item) => $this->looksLikeSoftware($item));

        return [
            $groups[0]->values()->all(),
            $groups[1]->values()->all(),
        ];
    }

    private function looksLikeSoftware(string $item): bool
    {
        $value = Str::lower(Str::ascii($item));

        return (bool) preg_match('/\b(jira|confluence|figma|miro|notion|slack|trello|asana|office|excel|power bi|tableau|salesforce|sap|sharepoint|github|gitlab|bitbucket|visual studio|vs code|intellij|eclipse|postman|insomnia|docker desktop|jenkins|azure devops|servicenow|wordpress|photoshop|illustrator|sketch|zeplin|windows|linux|macos)\b/u', $value);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function entriesFromSection(string $section): array
    {
        $blocks = preg_split("/\n{2,}/u", $section) ?: [];

        return collect($blocks)
            ->map(fn ($block) => trim($block))
            ->filter()
            ->map(function ($block) {
                $lines = collect(preg_split('/\R/u', $block) ?: [])
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values();

                $title = $lines->shift();
                $organization = $lines->shift();
                $period = $this->firstMatch('/(?:19|20)\d{2}\s*(?:-|–|a|al|to)?\s*(?:actual|presente|present|(?:19|20)\d{2})?/iu', $block);

                if ($period && $lines->first() === $period) {
                    $lines->shift();
                }

                return [
                    'title' => $title,
                    'organization' => $organization && $organization !== $period ? $organization : null,
                    'period' => $period,
                    'description' => trim($lines->implode("\n")) ?: null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * PDF text extraction can interleave columns, so some resumes expose dates and
     * companies without keeping them under the detected "Experiencia" heading.
     *
     * @param  array<int, string>  $lines
     * @return array<int, array<string, string|null>>
     */
    private function experienceEntriesFromLines(array $lines): array
    {
        $periodIndexes = [];

        foreach ($lines as $index => $line) {
            if ($this->looksLikeExperiencePeriod($line)) {
                $periodIndexes[] = $index;
            }
        }

        return collect($periodIndexes)
            ->map(function ($lineIndex, $entryIndex) use ($lines, $periodIndexes) {
                $nextPeriodIndex = $periodIndexes[$entryIndex + 1] ?? count($lines);
                $periodLine = $lines[$lineIndex];
                [$period, $organization] = $this->periodAndOrganization($periodLine);
                $descriptionLines = array_slice($lines, $lineIndex + 1, $nextPeriodIndex - $lineIndex - 1);

                return [
                    'title' => $this->titleBeforePeriod($lines, $lineIndex),
                    'organization' => $organization,
                    'period' => $period,
                    'description' => $this->descriptionFromLines($descriptionLines),
                ];
            })
            ->filter(fn ($entry) => filled($entry['period']) || filled($entry['organization']))
            ->values()
            ->all();
    }

    private function looksLikeExperiencePeriod(string $line): bool
    {
        return str_contains($line, '//')
            && preg_match('/(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre|actualidad|actual|presente|present|(?:19|20)\d{2})/iu', $line)
            && preg_match('/(?:19|20)\d{2}/', $line);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function periodAndOrganization(string $line): array
    {
        [$period, $organization] = array_pad(preg_split('/\/\//u', $line, 2) ?: [], 2, null);

        return [
            $period ? trim($period) : null,
            $organization ? trim($organization) : null,
        ];
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function titleBeforePeriod(array $lines, int $periodIndex): ?string
    {
        for ($index = $periodIndex - 1; $index >= max(0, $periodIndex - 8); $index--) {
            $title = $this->cleanTitle($lines[$index]);

            if ($title && ! in_array($this->headingKey($title), ['experiencia', 'formacion', 'formación', 'educacion', 'educación'], true)) {
                return $title;
            }
        }

        return null;
    }

    private function cleanTitle(string $line): ?string
    {
        $line = trim($line);

        if (preg_match('/\b(experiencia|formaci[oó]n|educaci[oó]n|habilidades|cursos|idiomas|informaci[oó]n)\b/iu', $line)) {
            return null;
        }

        if (preg_match('/^([A-ZÁÉÍÓÚÑ ]{8,})(?=[A-ZÁÉÍÓÚÑ][a-záéíóúñ])/u', $line, $matches)) {
            $line = trim($matches[1]);
        }

        if (! preg_match('/[A-ZÁÉÍÓÚÑ]{3}/u', $line)) {
            return null;
        }

        if (preg_match('/@|\d{4}|^\(?\d/iu', $line)) {
            return null;
        }

        return mb_convert_case($line, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function descriptionFromLines(array $lines): ?string
    {
        $description = collect($lines)
            ->reject(fn ($line) => filled($this->cleanTitle($line)))
            ->reject(fn ($line) => preg_match('/@|^\(?\d|habilidades|cursos|educaci[oó]n complementaria|idiomas/i', $line))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->implode("\n");

        return $description ?: null;
    }
}
