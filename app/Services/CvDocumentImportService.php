<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class CvDocumentImportService
{
    public function import(UploadedFile $file): array
    {
        $text = $this->extractText($file);

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
            'doc' => $this->extractDocText($path),
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
        $software = $this->itemsFromSection($sections['software'] ?? '');
        $skills = $this->itemsFromSection($sections['skills'] ?? '');

        if ($software === [] && $skills !== []) {
            [$software, $skills] = $this->partitionSoftwareItems($skills);
        }

        $summary = Arr::first([
            $sections['summary'] ?? null,
            $sections['profile'] ?? null,
            $sections['objective'] ?? null,
        ]);
        $experiences = $this->normalizeExperienceEntries($this->bestExperienceEntries($sections['experience'] ?? '', $lines->all()));

        return [
            'profile' => [
                'full_name' => $this->guessName($lines->all()),
                'email' => $this->firstMatch('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $cleanText),
                'phone' => $this->firstMatch('/(?:\+?\(?\d[\d\s().-]{7,}\d)/u', $this->contactSearchText($lines->all())),
                'headline' => $this->guessHeadline($lines->all()),
                'summary' => $summary,
                'linkedin_url' => $this->firstLink($cleanText, 'linkedin.com'),
                'portfolio_url' => $this->firstPortfolioLink($cleanText),
            ],
            'software' => $software,
            'skills' => $skills,
            'languages' => $this->languageItemsFromSection($sections['languages'] ?? ''),
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

    private function extractDocText(string $path): string
    {
        $candidates = array_filter([
            $this->extractDocTextWithAntiword($path),
            $this->extractDocTextWithLibreOffice($path),
        ]);

        $text = $this->bestPdfTextCandidate($candidates);

        if ($text === null) {
            throw new RuntimeException('No se pudo leer el archivo DOC. Intenta con un DOCX o habilita antiword/LibreOffice en el servidor para convertir documentos antiguos de Word.');
        }

        return $text;
    }

    private function extractPdfText(string $path): string
    {
        $candidates = array_filter([
            $this->extractPdfTextWithParser($path),
            $this->extractPdfTextWithPdftotext($path),
            $this->extractPdfTextWithOcr($path),
        ]);

        $text = $this->bestPdfTextCandidate($candidates);

        if ($text === null) {
            throw new RuntimeException('No se pudo extraer texto util del PDF. Puede venir como imagen o con una capa de texto incompatible. Intenta con un PDF exportado desde Word, DOCX o habilita pdftotext/tesseract en el servidor.');
        }

        return $text;
    }

    private function extractDocTextWithAntiword(string $path): string
    {
        return $this->runTextExtractorCommand(
            'antiword',
            ['-m', 'UTF-8.txt', $path],
        );
    }

    private function extractDocTextWithLibreOffice(string $path): string
    {
        $soffice = $this->binaryPath('soffice');

        if ($soffice === null) {
            return '';
        }

        $tempDir = storage_path('app/tmp/cv-import-doc-'.Str::uuid());

        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            return '';
        }

        try {
            $this->runCommand([
                $soffice,
                '--headless',
                '--convert-to',
                'txt:Text',
                '--outdir',
                $tempDir,
                $path,
            ]);

            $converted = $tempDir.DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME).'.txt';

            if (! is_file($converted)) {
                return '';
            }

            $text = file_get_contents($converted);

            return is_string($text) ? $this->normalizeText($text) : '';
        } catch (Throwable) {
            return '';
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/(?<=\S)\s*(?=(?:(?:Client|Customer)(?::|\s+[A-ZÁÉÍÓÚÑ])|Duration|Industry|Period|Languages?|Responsibilities\/Deliverables|Responsibilities \/ Deliverables):)/u', "\n", $text) ?? $text;
        $text = preg_replace('/(?<=\S)\s+(?=(?:Introduction|Profile|Technical Background|Management Background|Experience\/Project Work)\b)/u', "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function extractPdfTextWithParser(string $path): string
    {
        try {
            $parser = new PdfParser;
            $pdf = $parser->parseFile($path);

            return $this->normalizeText($pdf->getText());
        } catch (Throwable) {
            return '';
        }
    }

    private function extractPdfTextWithPdftotext(string $path): string
    {
        return $this->runTextExtractorCommand(
            'pdftotext',
            ['-layout', '-enc', 'UTF-8', $path, '-'],
        );
    }

    private function extractPdfTextWithOcr(string $path): string
    {
        if (! config('services.cv_import.pdf_ocr_enabled', true)) {
            return '';
        }

        $pdftoppm = $this->binaryPath('pdftoppm');
        $tesseract = $this->binaryPath('tesseract');

        if ($pdftoppm === null || $tesseract === null) {
            return '';
        }

        $tempDir = storage_path('app/tmp/cv-import-ocr-'.Str::uuid());

        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0777, true) && ! is_dir($tempDir)) {
            return '';
        }

        try {
            $prefix = $tempDir.DIRECTORY_SEPARATOR.'page';

            $this->runCommand([
                $pdftoppm,
                '-png',
                '-r',
                (string) config('services.cv_import.pdf_ocr_dpi', 180),
                $path,
                $prefix,
            ]);

            $images = glob($prefix.'-*.png') ?: [];

            if ($images === []) {
                return '';
            }

            sort($images);

            $language = (string) config('services.cv_import.pdf_ocr_language', 'spa+eng');
            $chunks = [];

            foreach ($images as $image) {
                $text = $this->runCommand([
                    $tesseract,
                    $image,
                    'stdout',
                    '-l',
                    $language,
                    '--psm',
                    '6',
                ]);

                if ($text !== '') {
                    $chunks[] = $text;
                }
            }

            return $this->normalizeText(implode("\n\n", $chunks));
        } catch (Throwable) {
            return '';
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    private function runTextExtractorCommand(string $binary, array $arguments): string
    {
        $resolved = $this->binaryPath($binary);

        if ($resolved === null) {
            return '';
        }

        try {
            return $this->normalizeText($this->runCommand([$resolved, ...$arguments]));
        } catch (Throwable) {
            return '';
        }
    }

    private function runCommand(array $command): string
    {
        $process = new Process($command);
        $process->setTimeout((int) config('services.cv_import.pdf_extract_timeout', 60));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    private function binaryPath(string $binary): ?string
    {
        $configured = config("services.cv_import.binaries.{$binary}");

        if (is_string($configured) && trim($configured) !== '') {
            return $configured;
        }

        return (new ExecutableFinder)->find($binary);
    }

    /**
     * @param  array<int, string>  $candidates
     */
    private function bestPdfTextCandidate(array $candidates): ?string
    {
        $bestText = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeText($candidate);
            $score = $this->pdfTextScore($normalized);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestText = $normalized;
            }
        }

        return $bestScore >= 50 ? $bestText : null;
    }

    private function pdfTextScore(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        $length = mb_strlen($text);
        $wordCount = preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\-+.@]{1,}/u', $text);
        $lineCount = count(array_filter(preg_split('/\R/u', $text) ?: [], fn ($line) => trim($line) !== ''));
        $emails = preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text);
        $links = preg_match_all('/https?:\/\/|linkedin\.com|github\.com/iu', $text);
        $badGlyphs = preg_match_all('/[�]/u', $text);
        $garbageRuns = preg_match_all('/[^\s\p{L}\p{N}]{6,}/u', $text);

        $score = 0;
        $score += min($length, 4000) / 40;
        $score += min((int) $wordCount, 250);
        $score += min((int) $lineCount * 2, 80);
        $score += (int) $emails * 20;
        $score += (int) $links * 10;
        $score -= (int) $badGlyphs * 12;
        $score -= (int) $garbageRuns * 8;

        return max(0, (int) round($score));
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $itemPath = $path.DIRECTORY_SEPARATOR.$item;

            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);

                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<string, string>
     */
    private function sections(array $lines): array
    {
        $aliases = [
            'summary' => ['resumen', 'resumen ejecutivo', 'extracto', 'sobre mi', 'acerca de mi', 'perfil profesional', 'professional summary', 'summary', 'introduction'],
            'profile' => ['perfil', 'profile', 'management background', 'technical background'],
            'objective' => ['objetivo', 'objective'],
            'experience' => ['experiencia', 'experiencia laboral', 'experiencia profesional', 'work experience', 'professional experience', 'employment', 'experience/project work', 'project work'],
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

        $previousKey = null;

        foreach ($lines as $line) {
            $key = $this->headingKey($line);

            if ($this->isTableHeaderSectionAlias($previousKey, $key)) {
                $previousKey = $key;

                continue;
            }

            if (isset($lookup[$key])) {
                $current = $lookup[$key];
                $sections[$current] ??= [];
                $previousKey = $key;

                continue;
            }

            if ($inlineSection = $this->inlineSectionMatch($line, $aliases)) {
                $current = $inlineSection['section'];
                $sections[$current] ??= [];

                if ($inlineSection['content'] !== '') {
                    $sections[$current][] = $inlineSection['content'];
                }

                $previousKey = $this->headingKey($line);

                continue;
            }

            if ($current) {
                $sections[$current][] = $line;
            }

            $previousKey = $key;
        }

        return collect($sections)
            ->map(fn ($sectionLines) => trim(implode("\n", $sectionLines)))
            ->map(function ($content, $section) {
                if ($section === 'summary') {
                    return $this->sanitizeSummarySection($content);
                }

                return $content;
            })
            ->filter(fn ($content) => $content !== '')
            ->all();
    }

    private function isTableHeaderSectionAlias(?string $previousKey, string $currentKey): bool
    {
        return $previousKey === 'elemento' && in_array($currentKey, ['experiencia', 'experience'], true);
    }

    private function headingKey(string $value): string
    {
        $value = Str::ascii(Str::lower(trim($value)));
        $value = preg_replace('/[^a-z ]/u', '', $value) ?? $value;

        return trim($value);
    }

    private function sanitizeSummarySection(string $section): string
    {
        $lines = collect(preg_split('/\R/u', $section) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return '';
        }

        $summary = [];
        $capturing = false;

        foreach ($lines as $line) {
            if ($this->isMetadataLabel($line)) {
                if ($capturing) {
                    break;
                }

                continue;
            }

            if (! $capturing && $this->looksLikeMetadataValue($line)) {
                continue;
            }

            $summary[] = $line;
            $capturing = true;
        }

        return trim(implode("\n", $summary));
    }

    private function isMetadataLabel(string $line): bool
    {
        if (! str_contains($line, ':')) {
            return false;
        }

        return in_array($this->headingKey(Str::before($line, ':')), [
            'full name',
            'name',
            'nombre',
            'nombre completo',
            'nationality',
            'nationalidad',
            'birthplace',
            'lugar de nacimiento',
            'address',
            'direccion',
            'location',
            'ubicacion',
            'cell phone',
            'phone',
            'telefono',
            'telefono celular',
            'emails',
            'email',
            'correo',
            'correos',
            'age',
            'edad',
            'profession',
            'profesion',
            'visa and pasaport',
            'visa and passport',
            'visa y pasaporte',
            'passport',
            'pasaporte',
            'linkedin',
            'portfolio',
        ], true);
    }

    private function looksLikeMetadataValue(string $line): bool
    {
        if (str_contains($line, '@') || preg_match('/(?:\+?\(?\d[\d\s().-]{7,}\d)/u', $line)) {
            return true;
        }

        return in_array($this->headingKey($line), [
            'mexican',
            'mexico df',
            'cdmx',
            'developer',
            'yes',
        ], true);
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

    /**
     * @param  array<int, string>  $lines
     */
    private function guessHeadline(array $lines): ?string
    {
        $name = $this->guessName($lines);

        foreach (array_slice($lines, 0, 12) as $line) {
            $trimmed = $this->normalizeHeadlineCandidate($line);

            if ($trimmed === '' || $trimmed === $name) {
                continue;
            }

            if (in_array($this->headingKey($trimmed), ['summary', 'introduction', 'profile'], true) || $this->isMetadataLabel($trimmed)) {
                continue;
            }

            if ($trimmed === '' || str_contains($trimmed, '@')) {
                continue;
            }

            if (mb_strlen($trimmed) <= 100) {
                return $trimmed;
            }
        }

        return $lines[1] ?? null;
    }

    private function normalizeHeadlineCandidate(string $line): string
    {
        $line = trim($line);
        $line = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '', $line) ?? $line;
        $line = preg_replace('/(?:\+?\(?\d[\d\s().-]{7,}\d)\s*$/u', '', $line) ?? $line;

        return trim($line, " \t\n\r\0\x0B|,-");
    }

    /**
     * @param  array<string, array<int, string>>  $aliases
     * @return array{section: string, content: string}|null
     */
    private function inlineSectionMatch(string $line, array $aliases): ?array
    {
        foreach ($aliases as $section => $headings) {
            foreach ($headings as $heading) {
                if (preg_match('/^'.preg_quote($heading, '/').'\s*:\s*(.+)$/iu', $line, $matches)) {
                    return [
                        'section' => $section,
                        'content' => trim($matches[1]),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function contactSearchText(array $lines): string
    {
        $window = [];

        foreach (array_slice($lines, 0, 40) as $line) {
            if (in_array($this->headingKey($line), ['experience', 'experiencia'], true)) {
                break;
            }

            $window[] = $line;
        }

        return implode("\n", $window);
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
            ->reject(fn ($item) => $this->isSectionNoiseItem($item))
            ->values()
            ->all();
    }

    private function isSectionNoiseItem(string $item): bool
    {
        $normalized = trim(Str::lower(Str::ascii($item)), " :");

        if ($normalized === '' || in_array($normalized, ['elemento', 'experiencia'], true)) {
            return true;
        }

        if (preg_match('/^\d+\s*(year|years|ano|anos|mes|meses)\b/u', $normalized)) {
            return true;
        }

        return $this->looksLikeSkillCategoryHeading($item);
    }

    private function looksLikeSkillCategoryHeading(string $item): bool
    {
        $normalized = trim(Str::lower(Str::ascii($item)), " :");

        if (in_array($normalized, [
            'lenguajes de programacion',
            'sistemas operativos',
            'case',
            'erp',
            'data ware house',
        ], true)) {
            return true;
        }

        return str_ends_with(trim($item), ':');
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
     * @return array<int, string>
     */
    private function languageItemsFromSection(string $section): array
    {
        $items = $this->itemsFromSection($section);

        return collect($items)
            ->flatMap(function ($item) {
                $prepared = preg_replace('/\b(Native|Fluent|Advanced|Intermediate|Basic|Conversational)\s+(?=[A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÑáéíóúñçÇãõÕüÜ-]+)/u', '$1|SPLIT|', $item) ?? $item;
                $parts = preg_split('/\|SPLIT\|/u', $prepared) ?: [$item];

                return collect($parts)
                    ->map(fn ($part) => trim($part, " \t\n\r\0\x0B.;"))
                    ->filter();
            })
            ->values()
            ->all();
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
     * @param  array<int, string>  $lines
     * @return array<int, array<string, string|null>>
     */
    private function bestExperienceEntries(string $section, array $lines): array
    {
        $candidates = [
            $this->entriesFromSection($section),
            $this->labeledRoleExperienceEntriesFromSection($section),
            $this->structuredExperienceEntriesFromSection($section),
            $this->durationBasedExperienceEntriesFromSection($section),
            $this->experienceEntriesFromLines($lines),
        ];

        $best = [];
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $score = $this->experienceEntriesScore($candidate);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param  array<int, array<string, string|null>>  $entries
     * @return array<int, array<string, string|null>>
     */
    private function normalizeExperienceEntries(array $entries): array
    {
        return collect($entries)
            ->map(function (array $entry) {
                $period = $this->normalizeExperiencePeriodValue($entry['period'] ?? null);
                $title = $entry['title'] ?? null;
                $organization = $entry['organization'] ?? null;
                $description = $entry['description'] ?? null;

                if ($period['trailing'] !== null && ! filled($title) && $this->looksLikeExperienceTitleCandidate($period['trailing'])) {
                    $title = $period['trailing'];
                }

                if (! filled($title) && filled($description)) {
                    [$inferredTitle, $remainingDescription] = $this->extractTitleFromDescription($description);

                    if ($inferredTitle !== null) {
                        $title = $inferredTitle;
                        $description = $remainingDescription;
                    }
                }

                if (filled($title) && ! filled($organization) && preg_match('/^(.*?)\s*\(([^()]+)\)$/u', $title, $matches)) {
                    $title = trim($matches[1]);
                    $organization = trim($matches[2]);
                }

                if (filled($title) && ! filled($organization) && $this->looksLikeOrganizationName($title) && filled($description)) {
                    [$inferredTitle, $remainingDescription] = $this->extractTitleFromDescription($description);

                    if ($inferredTitle !== null) {
                        $organization = $title;
                        $title = $inferredTitle;
                        $description = $remainingDescription;
                    }
                }

                return [
                    'title' => filled($title) ? $title : null,
                    'organization' => filled($organization) ? $organization : null,
                    'period' => $period['period'],
                    'description' => filled($description) ? $description : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{period: ?string, trailing: ?string}
     */
    private function normalizeExperiencePeriodValue(?string $period): array
    {
        if (! filled($period)) {
            return ['period' => null, 'trailing' => null];
        }

        $period = trim($period);

        if (preg_match('/^((?:(?:Jan|January|Feb|February|Mar|March|Apr|April|May|Jun|June|Jul|July|Aug|August|Sep|Sept|September|Oct|October|Nov|November|Dec|December|Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)\.?\s+\d{2,4}|\d{2}\/\d{4}|\d{4})\s*(?:-|–|a|to)\s*(?:(?:Jan|January|Feb|February|Mar|March|Apr|April|May|Jun|June|Jul|July|Aug|August|Sep|Sept|September|Oct|October|Nov|November|Dec|December|Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)\.?\s+\d{2,4}|\d{2}\/\d{4}|\d{4}|Actual|Present|Presente))\s+(.+)$/iu', $period, $matches)) {
            return [
                'period' => trim($matches[1]),
                'trailing' => trim($matches[2]),
            ];
        }

        return ['period' => $period, 'trailing' => null];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function extractTitleFromDescription(string $description): array
    {
        $lines = collect(preg_split('/\R/u', $description) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $first = $lines->first();

        if (! is_string($first)) {
            return [null, $description];
        }

        $candidate = preg_replace('/\s*Key expertise includes:\s*$/iu', '', $first) ?? $first;
        $candidate = trim($candidate, " \t\n\r\0\x0B.");

        if (! $this->looksLikeExperienceTitleCandidate($candidate)) {
            return [null, $description];
        }

        $lines->shift();
        $remaining = trim($lines->implode("\n"));

        return [$candidate, $remaining !== '' ? $remaining : null];
    }

    private function looksLikeExperienceTitleCandidate(string $value): bool
    {
        $value = trim($value);
        $normalized = Str::lower(Str::ascii($value));

        if ($value === '' || str_contains($value, '@')) {
            return false;
        }

        if (str_contains($normalized, 'key expertise includes')) {
            return true;
        }

        if (preg_match('/[.!?]\s/u', $value)) {
            return false;
        }

        if (mb_strlen($value) > 110) {
            return false;
        }

        return (bool) preg_match('/\b(manager|lead|consultant|architect|implementation|hypercare|developer|analyst|specialist|engineer|support)\b/iu', $value);
    }

    private function looksLikeOrganizationName(string $value): bool
    {
        if (preg_match('/\b(client|customer|responsibilities|duration)\b/iu', $value)) {
            return false;
        }

        return (bool) preg_match('/\b(group|automotive|international|services|consulting|mexico|panasonic|pepsico|accenture|ey|deloitte|avvale|saputo|kion)\b/iu', $value);
    }

    /**
     * @param  array<int, array<string, string|null>>  $entries
     */
    private function experienceEntriesScore(array $entries): int
    {
        $score = min(count($entries), 12) * 12;
        $previousOrganization = null;

        foreach ($entries as $entry) {
            if (filled($entry['title'] ?? $entry['position'] ?? null)) {
                $score += 15;
            }

            if (filled($entry['organization'] ?? $entry['company'] ?? null)) {
                $score += 15;
            }

            if (filled($entry['period'] ?? null)) {
                $score += 10;
            }

            if (filled($entry['description'] ?? null)) {
                $score += 5;
            }

            if (! filled($entry['title'] ?? null) && ! filled($entry['description'] ?? null)) {
                $score -= 25;
            }

            if (
                ! filled($entry['title'] ?? null)
                && filled($entry['organization'] ?? null)
                && $previousOrganization !== null
                && ($entry['organization'] ?? null) === $previousOrganization
            ) {
                $score -= 18;
            }

            if (mb_strlen((string) ($entry['description'] ?? '')) > 1200) {
                $score -= 12;
            }

            $previousOrganization = $entry['organization'] ?? null;
        }

        return max(0, $score);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function labeledRoleExperienceEntriesFromSection(string $section): array
    {
        $lines = collect(preg_split('/\R/u', $section) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $entries = [];
        $currentCompany = null;
        $employmentPeriod = null;
        $currentEntry = null;
        $description = [];

        $flushCurrent = function () use (&$entries, &$currentEntry, &$description): void {
            if ($currentEntry === null) {
                return;
            }

            $currentEntry['description'] = $description !== [] ? implode("\n", $description) : null;

            if (filled($currentEntry['title']) || filled($currentEntry['organization']) || filled($currentEntry['period'])) {
                $entries[] = $currentEntry;
            }

            $currentEntry = null;
            $description = [];
        };

        foreach ($lines as $index => $line) {
            if ($this->looksLikeLabeledRoleLine($line)) {
                $flushCurrent();

                $currentEntry = [
                    'title' => $this->labeledRoleTitle($line),
                    'organization' => $currentCompany,
                    'period' => $employmentPeriod,
                    'description' => null,
                ];

                continue;
            }

            if ($this->looksLikeLabeledCompanyLine($lines, $index)) {
                $flushCurrent();
                $currentCompany = $line;
                $employmentPeriod = null;

                continue;
            }

            if ($this->looksLikeLabeledDateLine($line)) {
                $period = $this->normalizeLabeledDate($line);

                if ($currentEntry !== null) {
                    $currentEntry['period'] = $period;
                } else {
                    $employmentPeriod = $period;
                }

                continue;
            }

            if ($currentEntry === null) {
                continue;
            }

            if ($this->isLabeledRoleMetaLabel($line)) {
                continue;
            }

            $normalized = trim(preg_replace('/^[\-*•➢]\s*/u', '', $line) ?? $line);

            if ($normalized !== '') {
                $description[] = $normalized;
            }
        }

        $flushCurrent();

        return $entries;
    }

    private function looksLikeLabeledRoleLine(string $line): bool
    {
        return (bool) preg_match('/^Rol y Proyecto:\s*(.+)/iu', $line);
    }

    private function labeledRoleTitle(string $line): string
    {
        return trim((string) preg_replace('/^Rol y Proyecto:\s*/iu', '', $line));
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function looksLikeLabeledCompanyLine(array $lines, int $index): bool
    {
        $line = trim($lines[$index] ?? '');

        if (
            $line === ''
            || $this->looksLikeLabeledRoleLine($line)
            || $this->looksLikeLabeledDateLine($line)
            || $this->isLabeledRoleMetaLabel($line)
            || preg_match('/^Proyecto\b/iu', $line)
        ) {
            return false;
        }

        $next = trim($lines[$index + 1] ?? '');
        $nextTwo = trim($lines[$index + 2] ?? '');

        return $this->looksLikeLabeledDateLine($next)
            || $this->looksLikeLabeledRoleLine($next)
            || ($this->looksLikeLabeledDateLine($next) && $this->looksLikeLabeledRoleLine($nextTwo));
    }

    private function looksLikeLabeledDateLine(string $line): bool
    {
        return (bool) preg_match('/^Fecha:\s*.+/iu', $line);
    }

    private function normalizeLabeledDate(string $line): string
    {
        $line = trim((string) preg_replace('/^Fecha:\s*/iu', '', $line));
        $line = preg_replace('/\s+a\s+/iu', ' - ', $line) ?? $line;

        return trim((string) (preg_replace('/(?<=\d{2}\/\d{4})\s+(?=(?:\d{2}\/\d{4}|Actual|Presente))/iu', ' - ', $line) ?? $line));
    }

    private function isLabeledRoleMetaLabel(string $line): bool
    {
        return (bool) preg_match('/^(Responsabilidades|Resultados\/Logros|Entorno):?$/iu', $line);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function structuredExperienceEntriesFromSection(string $section): array
    {
        $lines = collect(preg_split('/\R/u', $section) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $periodIndexes = [];

        foreach ($lines as $index => $line) {
            if ($this->looksLikeStructuredExperiencePeriod($line)) {
                $periodIndexes[] = $index;
            }
        }

        return collect($periodIndexes)
            ->map(function ($lineIndex, $entryIndex) use ($lines, $periodIndexes) {
                $nextPeriodIndex = $periodIndexes[$entryIndex + 1] ?? count($lines);
                $descriptionLines = array_slice($lines, $lineIndex + 1, $nextPeriodIndex - $lineIndex - 1);

                return [
                    'title' => $this->structuredExperienceTitle($lines, $lineIndex),
                    'organization' => $this->structuredExperienceOrganization($lines, $lineIndex),
                    'period' => $this->structuredExperiencePeriod($lines[$lineIndex]),
                    'description' => $this->structuredExperienceDescription($descriptionLines),
                ];
            })
            ->filter(fn ($entry) => filled($entry['title']) || filled($entry['organization']) || filled($entry['period']))
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
                $isStructured = $this->looksLikeStructuredExperiencePeriod($periodLine);
                $isLabeled = $this->looksLikeLabeledExperiencePeriod($periodLine);
                [$period, $organization] = $this->periodAndOrganization($periodLine);
                $descriptionLines = array_slice($lines, $lineIndex + 1, $nextPeriodIndex - $lineIndex - 1);

                return [
                    'title' => $isLabeled
                        ? $this->labeledExperienceTitle($lines, $lineIndex)
                        : ($isStructured
                        ? $this->structuredExperienceTitle($lines, $lineIndex)
                        : $this->titleBeforePeriod($lines, $lineIndex)),
                    'organization' => $isLabeled
                        ? $this->labeledExperienceOrganization($lines, $lineIndex)
                        : ($isStructured
                        ? $this->structuredExperienceOrganization($lines, $lineIndex)
                        : $organization),
                    'period' => $isLabeled
                        ? $this->labeledExperiencePeriod($periodLine)
                        : ($isStructured
                        ? $this->structuredExperiencePeriod($periodLine)
                        : $period),
                    'description' => $isLabeled
                        ? $this->labeledExperienceDescription($descriptionLines)
                        : ($isStructured
                        ? $this->structuredExperienceDescription($descriptionLines)
                        : $this->descriptionFromLines($descriptionLines)),
                ];
            })
            ->filter(fn ($entry) => filled($entry['period']) || filled($entry['organization']))
            ->values()
            ->all();
    }

    private function looksLikeExperiencePeriod(string $line): bool
    {
        return (
            str_contains($line, '//')
            && preg_match('/(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre|actualidad|actual|presente|present|(?:19|20)\d{2})/iu', $line)
            && preg_match('/(?:19|20)\d{2}/', $line)
        ) || $this->looksLikeStructuredExperiencePeriod($line) || $this->looksLikeLabeledExperiencePeriod($line);
    }

    private function looksLikeStructuredExperiencePeriod(string $line): bool
    {
        if (! str_contains($line, '/')) {
            return false;
        }

        return (bool) preg_match('/(?:jan|january|feb|february|mar|march|apr|april|may|jun|june|jul|july|aug|august|sep|sept|september|oct|october|nov|november|dec|december|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre).*(?:19|20)\d{2}/iu', $line);
    }

    private function looksLikeLabeledExperiencePeriod(string $line): bool
    {
        return (bool) preg_match('/^Period:\s*.+\d{2}\/\d{4}\s*(?:-|–)\s*\d{2}\/\d{4}/iu', trim($line));
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function durationBasedExperienceEntriesFromSection(string $section): array
    {
        $lines = collect(preg_split('/\R/u', $section) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $entries = [];
        $currentEntry = null;
        $description = [];

        $flushCurrent = function () use (&$entries, &$currentEntry, &$description): void {
            if ($currentEntry === null) {
                return;
            }

            if (! filled($currentEntry['title'] ?? null) && isset($description[0]) && ! $this->looksLikeDurationBasedDescriptionSentence($description[0])) {
                $currentEntry['title'] = array_shift($description);
            }

            $currentEntry['description'] = $description !== [] ? implode("\n", $description) : null;

            if (filled($currentEntry['title']) || filled($currentEntry['organization']) || filled($currentEntry['period'])) {
                $entries[] = $currentEntry;
            }

            $currentEntry = null;
            $description = [];
        };

        foreach ($lines as $index => $line) {
            if ($this->looksLikeDurationBasedExperienceLine($line)) {
                $flushCurrent();

                $currentEntry = [
                    'title' => $this->durationBasedExperienceTitle($lines, $index),
                    'organization' => $this->durationBasedExperienceOrganization($lines, $index),
                    'period' => $this->durationBasedExperiencePeriod($line),
                    'description' => null,
                ];

                continue;
            }

            if ($currentEntry === null) {
                continue;
            }

            if ($organization = $this->durationBasedOrganizationLabel($line)) {
                $currentEntry['organization'] ??= $organization;

                continue;
            }

            if ($this->isDurationBasedMetaLine($line)) {
                continue;
            }

            $normalized = trim(preg_replace('/^[\-*•➢]\s*/u', '', $line) ?? $line);

            if ($normalized !== '') {
                $description[] = $normalized;
            }
        }

        $flushCurrent();

        return $entries;
    }

    private function looksLikeDurationBasedExperienceLine(string $line): bool
    {
        return (bool) preg_match('/\bDuration:\s*.+/iu', $line);
    }

    private function durationBasedExperiencePeriod(string $line): ?string
    {
        if (! preg_match('/\bDuration:\s*(.+?)(?=\s+(?:Responsibilities\/Deliverables|Responsibilities \/ Deliverables|Client:|Customer:|Industry:)\b|$)/iu', $line, $matches)) {
            return null;
        }

        return trim($matches[1], " \t\n\r\0\x0B:");
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function durationBasedExperienceTitle(array $lines, int $index): ?string
    {
        for ($offset = 1; $offset <= 4; $offset++) {
            $previousLine = trim($lines[$index - $offset] ?? '');

            if ($previousLine === '') {
                continue;
            }

            if ((bool) preg_match('/^(?:Client|Customer|Responsibilities\/Deliverables|Responsibilities \/ Deliverables):?/iu', $previousLine)) {
                return null;
            }

            if ($this->looksLikeDurationBasedExperienceLine($previousLine)) {
                return null;
            }

            if ($this->isDurationBasedMetaLine($previousLine)) {
                continue;
            }

            if (! $this->looksLikeDurationBasedDescriptionSentence($previousLine)) {
                if (preg_match('/^(.*?)\s*\(([^()]+)\)\s*$/u', $previousLine, $matches)) {
                    return trim($matches[1]) ?: $previousLine;
                }

                return $previousLine;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function durationBasedExperienceOrganization(array $lines, int $index): ?string
    {
        $line = $lines[$index];

        if ($organization = $this->durationBasedOrganizationLabel($line)) {
            return $organization;
        }

        for ($offset = 1; $offset <= 4; $offset++) {
            $previousLine = trim($lines[$index - $offset] ?? '');

            if ($organization = $this->durationBasedOrganizationLabel($previousLine)) {
                return $organization;
            }

            if (preg_match('/\(([^()]+)\)\s*$/u', $previousLine, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function durationBasedOrganizationLabel(string $line): ?string
    {
        if (preg_match('/\b(?:Client|Customer):?\s*(.+?)(?=\s+Duration:\b|$)/iu', $line, $matches)) {
            return trim($matches[1], " \t\n\r\0\x0B:");
        }

        return null;
    }

    private function isDurationBasedMetaLine(string $line): bool
    {
        return (bool) preg_match('/^(?:Responsibilities\/Deliverables|Responsibilities \/ Deliverables|Industry|Client|Customer):?/iu', trim($line));
    }

    private function looksLikeDurationBasedDescriptionSentence(string $line): bool
    {
        return str_contains($line, '.') || mb_strlen($line) > 120;
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

    /**
     * @param  array<int, string>  $lines
     */
    private function structuredExperienceTitle(array $lines, int $periodIndex): ?string
    {
        for ($index = $periodIndex - 1; $index >= max(0, $periodIndex - 12); $index--) {
            if (preg_match('/^Position\s+(.+)/iu', $lines[$index], $matches)) {
                return trim($matches[1]);
            }
        }

        return $this->titleBeforePeriod($lines, $periodIndex);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function structuredExperienceOrganization(array $lines, int $periodIndex): ?string
    {
        $positionIndex = null;

        for ($index = $periodIndex - 1; $index >= max(0, $periodIndex - 12); $index--) {
            if (preg_match('/^Position\s+/iu', $lines[$index])) {
                $positionIndex = $index;
                break;
            }
        }

        $searchStart = $positionIndex !== null ? $positionIndex - 1 : $periodIndex - 1;

        for ($index = $searchStart; $index >= max(0, $searchStart - 10); $index--) {
            $line = trim($lines[$index]);

            if ($line === '' || $this->isStructuredExperienceLabel($line)) {
                continue;
            }

            if (preg_match('/^Project\b|^Place\b|^Date\b|^Responsibilit/iu', $line)) {
                continue;
            }

            return $line;
        }

        return null;
    }

    private function structuredExperiencePeriod(string $line): string
    {
        $parts = preg_split('/\s+\/\s+/u', trim($line), 2) ?: [];

        return trim($parts[1] ?? $line);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function structuredExperienceDescription(array $lines): ?string
    {
        $description = [];

        foreach ($lines as $line) {
            if (preg_match('/^(Tools|Referencia|cliente)\b/iu', $line)) {
                break;
            }

            if (preg_match('/\bCell:|\bEmail:/iu', $line)) {
                break;
            }

            if ($this->isStructuredExperienceLabel($line)) {
                if ($description !== []) {
                    break;
                }

                continue;
            }

            $normalized = trim(preg_replace('/^[\-*•➢]\s*/u', '', $line) ?? $line);

            if ($normalized === '' || mb_strlen($normalized) === 1) {
                continue;
            }

            $description[] = $normalized;
        }

        return $description !== [] ? implode("\n", $description) : null;
    }

    private function isStructuredExperienceLabel(string $line): bool
    {
        return (bool) preg_match('/^(Consulting|house\s*\/|Industry|Project\b|Project phase\b|Place\s*\/\s*Begin|Date- End Date|Responsibilitie|Responsibilitie\s*s|Responsibility|Responsibilities|Tools\b|Referencia\b|cliente\b)$/iu', trim($line));
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function labeledExperienceTitle(array $lines, int $periodIndex): ?string
    {
        for ($index = $periodIndex + 1; $index <= min(count($lines) - 1, $periodIndex + 4); $index++) {
            if (preg_match('/^Position:\s*(.+)/iu', $lines[$index], $matches)) {
                return trim($matches[1]);
            }
        }

        for ($index = $periodIndex - 1; $index >= max(0, $periodIndex - 4); $index--) {
            if (preg_match('/^Position:\s*(.+)/iu', $lines[$index], $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function labeledExperienceOrganization(array $lines, int $periodIndex): ?string
    {
        for ($index = $periodIndex - 1; $index >= max(0, $periodIndex - 4); $index--) {
            $line = trim($lines[$index]);

            if ($line === '' || preg_match('/^(Experience|Period:|Position:|Responsibilities:?)/iu', $line)) {
                continue;
            }

            return $line;
        }

        return null;
    }

    private function labeledExperiencePeriod(string $line): string
    {
        return trim((string) preg_replace('/^Period:\s*/iu', '', trim($line)));
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function labeledExperienceDescription(array $lines): ?string
    {
        $description = [];

        foreach ($lines as $line) {
            if ($this->looksLikeLabeledExperiencePeriod($line)) {
                break;
            }

            if (preg_match('/^Position:\s*/iu', $line)) {
                continue;
            }

            if (preg_match('/^Responsibilities:?\s*$/iu', $line)) {
                continue;
            }

            $normalized = trim(preg_replace('/^[\-*•➢]\s*/u', '', $line) ?? $line);

            if ($normalized === '') {
                continue;
            }

            $description[] = $normalized;
        }

        return $description !== [] ? implode("\n", $description) : null;
    }
}
