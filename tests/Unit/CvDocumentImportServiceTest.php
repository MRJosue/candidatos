<?php

namespace Tests\Unit;

use App\Services\CvDocumentImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CvDocumentImportServiceTest extends TestCase
{
    public function test_parse_text_populates_software_and_skills_without_undefined_variables(): void
    {
        $service = new CvDocumentImportService;

        $parsed = $service->parseText(implode("\n", [
            'Andrea Lopez',
            'Backend Engineer',
            'Habilidades',
            'Laravel',
            'PHP',
            'Software',
            'Jira',
            'GitHub',
        ]));

        $this->assertSame(['Jira', 'GitHub'], $parsed['software']);
        $this->assertSame(['Laravel', 'PHP'], $parsed['skills']);
    }

    public function test_best_pdf_text_candidate_prefers_useful_resume_text(): void
    {
        $service = new CvDocumentImportService;
        $method = new ReflectionMethod($service, 'bestPdfTextCandidate');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            '### @@ @@ ####',
            implode("\n", [
                'Carlos Poucell Arrona',
                'ABAP BTP CPI Consultant',
                'carlos@example.com',
                'https://linkedin.com/in/carlospoucell',
                'Experiencia',
                'SAP ABAP',
                'BTP',
                'CPI',
            ]),
        ]);

        $this->assertIsString($result);
        $this->assertStringContainsString('Carlos Poucell Arrona', $result);
        $this->assertStringContainsString('carlos@example.com', $result);
    }

    public function test_best_pdf_text_candidate_returns_null_for_garbage_only(): void
    {
        $service = new CvDocumentImportService;
        $method = new ReflectionMethod($service, 'bestPdfTextCandidate');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            '',
            '%%%%% &&&&& *****',
            "A\nB\nC",
        ]);

        $this->assertNull($result);
    }
}
