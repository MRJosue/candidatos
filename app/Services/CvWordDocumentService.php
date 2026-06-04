<?php

namespace App\Services;

use App\Models\CvProfile;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class CvWordDocumentService
{
    private const PAGE_WIDTH = 11906;
    private const PAGE_HEIGHT = 16838;
    private const MARGIN_TOP = 220;
    private const MARGIN_RIGHT = 560;
    private const MARGIN_BOTTOM = 1180;
    private const MARGIN_LEFT = 1440;
    private const CONTENT_WIDTH = 9906;
    private const SECTION_MARK_WIDTH = 720;
    private const COMPANY_BAND_WIDTH = 8560;
    private const COMPANY_BAND_INDENT = 580;
    private const SKILL_TABLE_WIDTH = 8120;
    private const ACCENT = '00B0F0';
    private const SOFT_GRAY = 'F2F2F2';

    public function output(CvProfile $profile): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cv-docx-');

        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del documento.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);

            throw new RuntimeException('No se pudo preparar el documento de Word.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relationshipsXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelationshipsXml());
        $zip->addFromString('word/_rels/header1.xml.rels', $this->headerRelationshipsXml());
        $zip->addFromString('word/document.xml', $this->documentXml($profile));
        $zip->addFromString('word/header1.xml', $this->headerXml());
        $zip->addFromString('word/footer1.xml', $this->footerXml());
        $zip->addFromString('word/styles.xml', $this->stylesXml());
        $zip->addFromString('word/numbering.xml', $this->numberingXml());
        $zip->addFromString('word/settings.xml', $this->settingsXml());

        if ($logo = $this->logoContents()) {
            $zip->addFromString('word/media/act-digital-logo.png', $logo);
        }

        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el documento de Word.');
        }

        return $contents;
    }

    private function documentXml(CvProfile $profile): string
    {
        $labels = $this->labels($profile->language ?: 'es');
        $body = [];

        $body[] = $this->topBlock($profile);

        if (filled($profile->summary)) {
            $body[] = $this->sectionTitle($labels['summary']);
            $body[] = $this->paragraph($profile->summary, 'ActBody');
        }

        if (filled($profile->objective)) {
            $body[] = $this->sectionTitle($labels['objective']);
            $body[] = $this->paragraph($profile->objective, 'ActBody');
        }

        if (filled($technicalSkills = $this->skillText($profile, 'skill'))) {
            $body[] = $this->paragraph($profile->skills_section_title ?: $labels['skills'], 'ActSummarySkillHeading');
            $body[] = $this->paragraph($technicalSkills, 'ActSummarySkillLine');
        }

        $body = array_merge($body, $this->experiences($profile, $labels));
        $body = array_merge($body, $this->education($profile, $labels));
        $body = array_merge($body, $this->technicalSkills($profile, $labels));
        $body = array_merge($body, $this->profileExtras($profile, $labels));

        $body[] = $this->sectionProperties();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<w:body>'.implode('', $body).'</w:body>'
            .'</w:document>';
    }

    private function experiences(CvProfile $profile, array $labels): array
    {
        if ($profile->experiences->isEmpty()) {
            return [];
        }

        $body = [$this->sectionTitle($labels['experience'])];

        foreach ($profile->experiences as $experience) {
            $companyLine = collect([$experience->company, $experience->location])
                ->filter()
                ->implode(' | ');

            if (filled($companyLine)) {
                $body[] = $this->band($companyLine);
            }

            if (filled($experience->position)) {
                $body[] = $this->paragraph($labels['position'].': '.$experience->position, 'ActItem');
            }

            $period = $this->period($experience->start_date, $experience->is_current ? null : $experience->end_date, $experience->is_current ? $labels['current'] : null);

            if (filled($period)) {
                $body[] = $this->paragraph($labels['period'].': '.$period, 'ActMeta');
            }

            if ($this->lines($experience->description)->isNotEmpty()) {
                $body[] = $this->paragraph($labels['functions'].':', 'ActItem');

                foreach ($this->lines($experience->description) as $line) {
                    $body[] = $this->bullet($line);
                }
            }

            if (filled($experience->tools_used)) {
                $body[] = $this->paragraph($labels['tools'].': '.$experience->tools_used, 'ActMeta');
            }
        }

        return $body;
    }

    private function education(CvProfile $profile, array $labels): array
    {
        if ($profile->education->isEmpty()) {
            return [];
        }

        $body = [$this->sectionTitle($labels['education'])];

        foreach ($profile->education as $education) {
            if (filled($education->institution)) {
                $body[] = $this->paragraph($education->institution, 'ActItem');
            }

            $degree = collect([$education->degree, $education->field])
                ->filter()
                ->implode(' - ');

            if (filled($degree)) {
                $body[] = $this->paragraph($degree, 'ActBody');
            }

            $period = $this->period($education->start_date, $education->end_date);

            if (filled($period)) {
                $body[] = $this->paragraph($period, 'ActMeta');
            }

            foreach ($this->educationDescriptionLines($education->description) as $line) {
                $body[] = $this->bullet($line);
            }
        }

        return $body;
    }

    private function technicalSkills(CvProfile $profile, array $labels): array
    {
        $software = $this->skillText($profile, 'software');
        $languages = $this->skillText($profile, 'language');
        $certifications = $this->skillText($profile, 'certification') ?: $this->lines($profile->awards)->implode("\n");

        if (! filled($software.$languages.$certifications)) {
            return [];
        }

        $body = [$this->sectionTitle($labels['technical_skills'])];
        $body[] = $this->skillsTable([
            $labels['software'] => $software,
            $labels['languages'] => $languages,
            $labels['awards'] => $certifications,
        ]);

        return $body;
    }

    private function profileExtras(CvProfile $profile, array $labels): array
    {
        $body = [];

        foreach ([
            'leadership_activities' => $labels['leadership'],
            'interests' => $labels['interests'],
        ] as $field => $title) {
            if (filled($profile->{$field})) {
                $body[] = $this->sectionTitle($title);
                $body[] = $this->paragraph($profile->{$field}, 'ActBody');
            }
        }

        return $body;
    }

    private function skillText(CvProfile $profile, string $type): string
    {
        return $profile->skills
            ->filter(fn ($skill) => ($skill->type ?: 'skill') === $type)
            ->map(fn ($skill) => $skill->name.($skill->level ? ' ('.$skill->level.'/5)' : ''))
            ->filter()
            ->implode('; ');
    }

    private function sectionTitle(string $title): string
    {
        return '<w:tbl>'.$this->tableProperties([self::SECTION_MARK_WIDTH, self::CONTENT_WIDTH - self::SECTION_MARK_WIDTH])
            .'<w:tr>'
            .$this->tableCell('', self::SECTION_MARK_WIDTH, self::ACCENT, 'ActSection', ['text' => 'center'])
            .$this->tableCell(mb_strtoupper($title), self::CONTENT_WIDTH - self::SECTION_MARK_WIDTH, null, 'ActSection')
            .'</w:tr>'
            .'</w:tbl>';
    }

    private function band(string $text): string
    {
        return '<w:tbl>'.$this->tableProperties([self::COMPANY_BAND_WIDTH], false, self::COMPANY_BAND_INDENT)
            .'<w:tr>'.$this->tableCell(mb_strtoupper($text), self::COMPANY_BAND_WIDTH, 'EEEEEE', 'ActBand').'</w:tr>'
            .'</w:tbl>';
    }

    private function skillsTable(array $columns): string
    {
        $widths = [2110, 1950, 4060];
        $headerCells = '';
        $valueCells = '';
        $index = 0;

        foreach ($columns as $heading => $text) {
            $width = $widths[$index] ?? 2553;
            $headerCells .= $this->tableCell(mb_strtoupper($heading), $width, 'BFBFBF', 'ActSkillHeader', ['border' => true]);
            $valueCells .= $this->tableCell($text ?: ' ', $width, self::SOFT_GRAY, 'ActSkillValue', ['border' => true]);
            $index++;
        }

        return '<w:tbl>'.$this->tableProperties($widths, true)
            .'<w:tr>'.$headerCells.'</w:tr>'
            .'<w:tr>'.$valueCells.'</w:tr>'
            .'</w:tbl>';
    }

    private function topBlock(CvProfile $profile): string
    {
        $contact = collect([$profile->email, $profile->phone, $profile->location, $profile->portfolio_url])
            ->filter()
            ->implode("\n");

        $left = '<w:p><w:pPr><w:pStyle w:val="ActName"/></w:pPr>'.$this->runs($profile->full_name ?: $profile->title ?: 'CV').'</w:p>';

        if (filled($profile->headline)) {
            $left .= '<w:p><w:pPr><w:pStyle w:val="ActRole"/></w:pPr>'.$this->runs($profile->headline).'</w:p>';
        }

        if (filled($profile->tagline)) {
            $left .= '<w:p><w:pPr><w:pStyle w:val="ActRole"/></w:pPr>'.$this->runs($profile->tagline).'</w:p>';
        }

        $right = filled($contact)
            ? '<w:p><w:pPr><w:pStyle w:val="ActContact"/><w:jc w:val="right"/></w:pPr>'.$this->runs($contact).'</w:p>'
            : '<w:p><w:pPr><w:pStyle w:val="ActContact"/></w:pPr></w:p>';

        return '<w:tbl>'.$this->tableProperties([6728, 2616])
            .'<w:tr>'
            .'<w:tc><w:tcPr><w:tcW w:w="6728" w:type="dxa"/><w:tcBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/></w:tcBorders></w:tcPr>'.$left.'</w:tc>'
            .'<w:tc><w:tcPr><w:tcW w:w="2616" w:type="dxa"/><w:tcBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/></w:tcBorders></w:tcPr>'.$right.'</w:tc>'
            .'</w:tr>'
            .'</w:tbl>';
    }

    private function tableProperties(array $widths, bool $borders = false, int $indent = 0): string
    {
        $total = array_sum($widths);
        $borderXml = $borders
            ? '<w:tblBorders><w:top w:val="single" w:sz="3" w:space="0" w:color="BFBFBF"/><w:left w:val="single" w:sz="3" w:space="0" w:color="BFBFBF"/><w:bottom w:val="single" w:sz="3" w:space="0" w:color="BFBFBF"/><w:right w:val="single" w:sz="3" w:space="0" w:color="BFBFBF"/><w:insideH w:val="single" w:sz="3" w:space="0" w:color="BFBFBF"/><w:insideV w:val="single" w:sz="3" w:space="0" w:color="BFBFBF"/></w:tblBorders>'
            : '<w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders>';

        $indentXml = $indent > 0 ? '<w:tblInd w:w="'.$indent.'" w:type="dxa"/>' : '';

        return '<w:tblPr><w:tblW w:w="'.$total.'" w:type="dxa"/>'.$indentXml.$borderXml.'<w:tblCellMar><w:top w:w="35" w:type="dxa"/><w:left w:w="60" w:type="dxa"/><w:bottom w:w="35" w:type="dxa"/><w:right w:w="60" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tblGrid>'.collect($widths)->map(fn ($width) => '<w:gridCol w:w="'.$width.'"/>')->implode('').'</w:tblGrid>';
    }

    private function tableCell(string $text, int $width, ?string $fill, string $style, array $options = []): string
    {
        $shading = $fill ? '<w:shd w:val="clear" w:color="auto" w:fill="'.$fill.'"/>' : '';
        $textAlign = ($options['text'] ?? null) === 'center' ? '<w:jc w:val="center"/>' : '';
        $border = ($options['border'] ?? false) ? '' : '<w:tcBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/></w:tcBorders>';

        return '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'.$shading.$border.'</w:tcPr>'
            .'<w:p><w:pPr><w:pStyle w:val="'.$style.'"/>'.$textAlign.'</w:pPr>'.$this->runs($text).'</w:p>'
            .'</w:tc>';
    }

    private function period($startDate, $endDate, ?string $currentLabel = null): ?string
    {
        $start = $startDate?->format('m/Y');
        $end = $currentLabel ?: $endDate?->format('m/Y');

        return collect([$start, $end])->filter()->implode(' - ') ?: null;
    }

    private function lines(?string $value): Collection
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();
    }

    private function educationDescriptionLines(?string $value): Collection
    {
        return $this->lines($value)
            ->reject(fn (string $line) => (bool) preg_match('/\b(?:promedio|gpa)\b/i', $line))
            ->values();
    }

    private function bullet(string $text): string
    {
        return '<w:p><w:pPr><w:pStyle w:val="ActBullet"/><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr>'.$this->runs($text).'</w:p>';
    }

    private function paragraph(?string $text, string $style = 'ActBody'): string
    {
        return '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/></w:pPr>'.$this->runs((string) $text).'</w:p>';
    }

    private function runs(string $text): string
    {
        return collect(explode("\n", $text))
            ->map(fn ($line, $index) => ($index > 0 ? '<w:br/>' : '').'<w:r><w:t xml:space="preserve">'.$this->escape($line).'</w:t></w:r>')
            ->implode('');
    }

    private function sectionProperties(): string
    {
        return '<w:sectPr>'
            .'<w:headerReference w:type="default" r:id="rId3"/>'
            .'<w:footerReference w:type="default" r:id="rId4"/>'
            .'<w:pgSz w:w="'.self::PAGE_WIDTH.'" w:h="'.self::PAGE_HEIGHT.'"/>'
            .'<w:pgMar w:top="'.self::MARGIN_TOP.'" w:right="'.self::MARGIN_RIGHT.'" w:bottom="'.self::MARGIN_BOTTOM.'" w:left="'.self::MARGIN_LEFT.'" w:header="279" w:footer="708" w:gutter="0"/>'
            .'<w:cols w:space="708"/><w:docGrid w:linePitch="360"/>'
            .'</w:sectPr>';
    }

    private function labels(string $language): array
    {
        return $language === 'en'
            ? [
                'summary' => 'Professional Summary',
                'objective' => 'Objective',
                'experience' => 'Work Experience',
                'education' => 'Education',
                'position' => 'Position',
                'period' => 'Period',
                'functions' => 'Responsibilities',
                'current' => 'Present',
                'tools' => 'Tools Used',
                'technical_skills' => 'Technical Skills and Certifications',
                'software' => 'Software',
                'skills' => 'Skills',
                'languages' => 'Languages',
                'awards' => 'Certifications',
                'leadership' => 'Leadership and activities',
                'interests' => 'Interests',
                'gpa' => 'GPA',
                'honors' => 'Honors',
                'thesis' => 'Thesis',
                'coursework' => 'Relevant coursework',
            ]
            : [
                'summary' => 'Resumen',
                'objective' => 'Objetivo',
                'experience' => 'Experiencia profesional',
                'education' => 'Educación',
                'position' => 'Puesto',
                'period' => 'Periodo',
                'functions' => 'Funciones',
                'current' => 'Actual',
                'tools' => 'Herramientas utilizadas',
                'technical_skills' => 'Habilidades técnicas y certificaciones',
                'software' => 'Software',
                'skills' => 'Lenguajes',
                'languages' => 'Idiomas',
                'awards' => 'Certificaciones',
                'leadership' => 'Liderazgo y actividades',
                'interests' => 'Intereses',
                'gpa' => 'Promedio',
                'honors' => 'Honores',
                'thesis' => 'Tesis',
                'coursework' => 'Cursos relevantes',
            ];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function logoContents(): ?string
    {
        $path = public_path('images/cv-templates/act-digital-logo.png');

        return file_exists($path) ? file_get_contents($path) ?: null : null;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Default Extension="png" ContentType="image/png"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>'
            .'<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
            .'<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
            .'</Types>';
    }

    private function relationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';
    }

    private function documentRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>'
            .'<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
            .'<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>'
            .'</Relationships>';
    }

    private function headerRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/act-digital-logo.png"/>'
            .'</Relationships>';
    }

    private function headerXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<w:p><w:pPr><w:pStyle w:val="Header"/><w:jc w:val="right"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="1470660" cy="1257300"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:docPr id="1" name="ACT Digital logo"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="1" name="ACT Digital logo"/><pic:cNvPicPr><a:picLocks noChangeAspect="1"/></pic:cNvPicPr></pic:nvPicPr><pic:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1470660" cy="1257300"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>'
            .'</w:hdr>';
    }

    private function footerXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:p><w:pPr><w:pStyle w:val="Footer"/><w:pBdr><w:top w:val="single" w:sz="8" w:space="1" w:color="'.self::ACCENT.'"/></w:pBdr></w:pPr><w:r><w:t>actdigital.com</w:t></w:r></w:p>'
            .'</w:ftr>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="0"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .$this->style('Normal', 'Normal', 20, '262626')
            .$this->style('ActBody', 'ACT Body', 20, '000000', false, 0, 80, 'both')
            .$this->style('ActName', 'ACT Name', 40, '808080', true, 0, 0)
            .$this->style('ActRole', 'ACT Role', 24, '808080', false, 0, 220)
            .$this->style('ActContact', 'ACT Contact', 17, '6F6F6F', false, 0, 0)
            .$this->style('ActSection', 'ACT Section', 30, '808080', true, 90, 90)
            .$this->style('ActBand', 'ACT Band', 28, '808080', true, 20, 20)
            .$this->style('ActItem', 'ACT Item', 20, '000000', true, 80, 20)
            .$this->style('ActMeta', 'ACT Meta', 20, '000000', false, 20, 20)
            .$this->style('ActBullet', 'ACT Bullet', 20, '000000', false, 20, 20)
            .$this->style('ActSummarySkillHeading', 'ACT Summary Skill Heading', 20, '000000', true, 80, 0)
            .$this->style('ActSummarySkillLine', 'ACT Summary Skill Line', 20, '000000', true, 0, 80)
            .$this->style('ActSkillHeader', 'ACT Skill Header', 22, '000000', true, 60, 60)
            .$this->style('ActSkillValue', 'ACT Skill Value', 19, '000000', false, 70, 70)
            .$this->style('Header', 'Header', 20, '000000')
            .$this->style('Footer', 'Footer', 16, '666666')
            .'</w:styles>';
    }

    private function style(string $id, string $name, int $size, string $color, bool $bold = false, int $before = 0, int $after = 0, ?string $jc = null): string
    {
        return '<w:style w:type="paragraph" w:styleId="'.$id.'">'
            .'<w:name w:val="'.$name.'"/>'
            .'<w:pPr><w:spacing w:before="'.$before.'" w:after="'.$after.'"/>'.($jc ? '<w:jc w:val="'.$jc.'"/>' : '').'</w:pPr>'
            .'<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>'
            .($bold ? '<w:b/><w:bCs/>' : '')
            .'<w:color w:val="'.$color.'"/><w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/></w:rPr>'
            .'</w:style>';
    }

    private function numberingXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:abstractNum w:abstractNumId="0"><w:multiLevelType w:val="hybridMultilevel"/><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/><w:lvlJc w:val="left"/><w:pPr><w:ind w:left="360" w:hanging="180"/></w:pPr><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:hint="default"/></w:rPr></w:lvl></w:abstractNum>'
            .'<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num>'
            .'</w:numbering>';
    }

    private function settingsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:defaultTabStop w:val="720"/>'
            .'</w:settings>';
    }
}
