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

    public function test_parse_text_handles_resume_with_fecha_and_rol_y_proyecto_blocks(): void
    {
        $service = new CvDocumentImportService;

        $parsed = $service->parseText(implode("\n", [
            'Ariel García Colín',
            'Consultor SAP SD / TI',
            'Resumen Ejecutivo',
            'Consultor SAP con experiencia en proyectos enterprise.',
            'Habilidades',
            'LENGUAJES DE PROGRAMACION:',
            'Elemento',
            'Experiencia',
            'ABAP',
            '4 Years',
            'PHP',
            '1 Year',
            'ERP:',
            'SAP',
            '11 años',
            'Experiencia Laboral',
            'HAND BRIDGE Consulting (México)',
            'Fecha: 10/2023 a 09/2025',
            'Rol y Proyecto: ERP SAP Senior SD Specialyst',
            'Proyecto Rainbow , Prospira, TAWA, ZD',
            'Fecha: 10/2023 a Actual',
            'Responsabilidades:',
            'Participación en el proyecto configuración y desarrollo en el proceso de facturación.',
            'Entorno:',
            'SAP R/3 6.5, S/4 hana',
            'NTT DATA Services (México)',
            'Rol y Proyecto: ERP SAP Senior SD Specialyst',
            'Proyecto Dematic',
            'Fecha: 02/2022 a 07/2022',
            'Responsabilidades:',
            'Configuración de SAP SD',
            'Jira',
        ]));

        $this->assertSame('Consultor SAP con experiencia en proyectos enterprise.', $parsed['profile']['summary']);
        $this->assertSame(['ABAP', 'PHP'], $parsed['skills']);
        $this->assertContains('SAP', $parsed['software']);
        $this->assertCount(2, $parsed['experiences']);
        $this->assertSame('ERP SAP Senior SD Specialyst', $parsed['experiences'][0]['title']);
        $this->assertSame('HAND BRIDGE Consulting (México)', $parsed['experiences'][0]['organization']);
        $this->assertSame('10/2023 - Actual', $parsed['experiences'][0]['period']);
        $this->assertStringContainsString('Proyecto Rainbow', $parsed['experiences'][0]['description'] ?? '');
        $this->assertSame('NTT DATA Services (México)', $parsed['experiences'][1]['organization']);
        $this->assertSame('02/2022 - 07/2022', $parsed['experiences'][1]['period']);
    }

    public function test_parse_text_handles_inline_duration_client_labels_and_custom_headings(): void
    {
        $service = new CvDocumentImportService;

        $parsed = $service->parseText(implode("\n", [
            'Efrén Serrano',
            'SAP MM/WM/EWM/SD Senior Manager Consultant +1 210 803 1773',
            'Introduction',
            'Efren has led end-to-end capability development across Manufacturing and SAP EWM.',
            'Profile',
            'Proactive, committed and results-driven SAP Consultant with over 12 years of IT experience.',
            'Experience/Project Work',
            'DC Delivery Manager (Accenture)',
            'Industry: Consulting, Monterrey MX Duration: October 26 – Actual Responsibilities/Deliverables:',
            'Expert in multi-country SAP logistics implementations across MM, SD, WM, LE-TRA, TM, EWM, QM y PP.',
            'Industry: AutomotiveDuration: Jul 24 –Oct 26',
            'Responsibilities/Deliverables:',
            'Client: EY',
            'Automotive JIT/JIS & EDI Integration Experience',
            'Key expertise includes:',
            'Configuration and support of SAP Automotive JIT and JIS Call processing.',
            'Responsibilities / Deliverables Client: AVVALE Duration: Jan 23 – Jul 24',
            'Led the design and implementation of SAP S/4HANA Cloud Extended Warehouse Management (EWM) solutions.',
            'Languages: Spanish - Native English – Fluent',
            'Portugues-Conversational.',
        ]));

        $this->assertSame('SAP MM/WM/EWM/SD Senior Manager Consultant', $parsed['profile']['headline']);
        $this->assertSame('Efren has led end-to-end capability development across Manufacturing and SAP EWM.', $parsed['profile']['summary']);
        $this->assertSame('+1 210 803 1773', $parsed['profile']['phone']);
        $this->assertSame(['Spanish - Native', 'English – Fluent', 'Portugues-Conversational'], $parsed['languages']);
        $this->assertCount(3, $parsed['experiences']);
        $this->assertSame('DC Delivery Manager', $parsed['experiences'][0]['title']);
        $this->assertSame('Accenture', $parsed['experiences'][0]['organization']);
        $this->assertSame('October 26 – Actual', $parsed['experiences'][0]['period']);
        $this->assertStringContainsString('multi-country SAP logistics implementations', $parsed['experiences'][0]['description'] ?? '');
        $this->assertSame('EY', $parsed['experiences'][1]['organization']);
        $this->assertSame('Jul 24 –Oct 26', $parsed['experiences'][1]['period']);
        $this->assertSame('Automotive JIT/JIS & EDI Integration Experience', $parsed['experiences'][1]['title']);
        $this->assertSame('AVVALE', $parsed['experiences'][2]['organization']);
        $this->assertSame('Jan 23 – Jul 24', $parsed['experiences'][2]['period']);
        $this->assertSame('Led the design and implementation of SAP S/4HANA Cloud Extended Warehouse Management (EWM) solutions', $parsed['experiences'][2]['title']);
    }
}
