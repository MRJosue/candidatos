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

    public function test_parse_text_trims_summary_before_personal_metadata_and_detects_headline(): void
    {
        $service = new CvDocumentImportService;

        $parsed = $service->parseText(implode("\n", [
            'Jonathan Vazquez Hernandez',
            'Summary',
            'Full Name:',
            'Computer engineer',
            'Jonathan Vázquez has been a developer of SAP PI for 14 years.',
            'Dedicated to the management of software consulting.',
            'Nationality:',
            'Mexican',
            'Cell Phone:',
            '(52) 55-32-01-52-97',
            'Emails:',
            'jvh_017@hotmail.com',
            'EXPERIENCE',
            'WSP',
        ]));

        $this->assertSame('Computer engineer', $parsed['profile']['headline']);
        $this->assertSame(implode("\n", [
            'Computer engineer',
            'Jonathan Vázquez has been a developer of SAP PI for 14 years.',
            'Dedicated to the management of software consulting.',
        ]), $parsed['profile']['summary']);
        $this->assertSame('(52) 55-32-01-52-97', $parsed['profile']['phone']);
        $this->assertStringNotContainsString('Nationality', $parsed['profile']['summary'] ?? '');
        $this->assertStringNotContainsString('jvh_017@hotmail.com', $parsed['profile']['summary'] ?? '');
    }

    public function test_parse_text_extracts_structured_experience_entries_from_resume_blocks(): void
    {
        $service = new CvDocumentImportService;

        $parsed = $service->parseText(implode("\n", [
            'Jonathan Vazquez Hernandez',
            'Summary',
            'Computer engineer',
            'EXPERIENCE',
            'Consulting',
            'house /',
            'Industry',
            'WSP',
            'Project - AMS',
            'Position SAP CPI Sr.',
            'Project phase - improvement',
            'Place / Begin',
            'Date- End Date',
            'Mexico city / March 03 2025 – December 22 2025',
            'Responsibilities',
            '• Provided operational support by addressing and resolving tickets.',
            'Tools • CPI, SOAP UI',
            'Referencia',
            'cliente',
            'Consulting',
            'house /',
            'Industry',
            'Accenture',
            'Project - AMS',
            'Position SAP CPI Sr.',
            'Project phase - improvement',
            'Place / Begin',
            'Date- End Date',
            'Mexico city / Jul 01 2025 - Oct 24 2025',
            'Responsibilities',
            '• Monitored and handled incidents in ServiceNow.',
        ]));

        $this->assertCount(2, $parsed['experiences']);
        $this->assertSame('SAP CPI Sr.', $parsed['experiences'][0]['title']);
        $this->assertSame('WSP', $parsed['experiences'][0]['organization']);
        $this->assertSame('March 03 2025 – December 22 2025', $parsed['experiences'][0]['period']);
        $this->assertStringContainsString('Provided operational support', $parsed['experiences'][0]['description'] ?? '');
        $this->assertSame('Accenture', $parsed['experiences'][1]['organization']);
    }

    public function test_parse_text_extracts_labeled_pdf_style_experience_entries(): void
    {
        $service = new CvDocumentImportService;

        $parsed = $service->parseText(implode("\n", [
            'Jonathan Vazquez Hernandez',
            'Computer Engineer, SAP PI/PO/CPI Developer',
            'EXPERIENCE',
            'WSP',
            'Period: 01/2025 - 12/2025',
            'Position: SAP CPI Sr.',
            'Responsibilities:',
            'Executed cutovers during release cycles.',
            'Generated and maintained API Key–based certificates.',
            'Accenture',
            'Period: 07/2025 - 10/2025',
            'Position: SAP CPI Sr.',
            'Responsibilities:',
            'Monitored and handled incidents in ServiceNow.',
        ]));

        $this->assertCount(2, $parsed['experiences']);
        $this->assertSame('WSP', $parsed['experiences'][0]['organization']);
        $this->assertSame('01/2025 - 12/2025', $parsed['experiences'][0]['period']);
        $this->assertSame('SAP CPI Sr.', $parsed['experiences'][0]['title']);
        $this->assertStringContainsString('Executed cutovers', $parsed['experiences'][0]['description'] ?? '');
    }
}
