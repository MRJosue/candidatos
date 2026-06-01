<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class CvWordDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_download_cv_as_word_document(): void
    {
        $user = User::factory()->create();
        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV Andrea Lopez',
            'full_name' => 'Andrea Lopez',
            'email' => 'andrea@example.com',
            'headline' => 'Laravel Engineer',
            'summary' => 'Construye productos internos.',
            'awards' => "Oracle Cloud Infrastructure Foundations\nScrum Master",
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $profile->experiences()->create([
            'company' => 'Acme',
            'position' => 'Backend Developer',
            'start_date' => '2024-01-01',
            'is_current' => true,
            'description' => 'Desarrollo de APIs.',
        ]);
        $profile->education()->create([
            'institution' => 'Universidad Demo',
            'degree' => 'Engineering',
            'gpa' => '9 average',
            'description' => "Promedio GPA 9\nProyecto final de integraciones",
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('cv.download-word', $profile));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->assertStringStartsWith('PK', $response->getContent());
        $document = $this->docxDocumentXml($response->getContent());

        $this->assertStringContainsString('Andrea Lopez', $document);
        $this->assertStringContainsString('Backend Developer', $document);
        $this->assertStringContainsString('CERTIFICACIONES', $document);
        $this->assertStringContainsString('Oracle Cloud Infrastructure Foundations', $document);
        $this->assertStringNotContainsString('9 average', $document);
        $this->assertStringNotContainsString('Promedio GPA 9', $document);
        $this->assertStringContainsString('Proyecto final de integraciones', $document);

        $certificationsPosition = strpos($document, 'CERTIFICACIONES');
        $awardPosition = strpos($document, 'Oracle Cloud Infrastructure Foundations');

        $this->assertIsInt($certificationsPosition);
        $this->assertIsInt($awardPosition);
        $this->assertGreaterThan($certificationsPosition, $awardPosition);
    }

    public function test_user_can_download_selected_talent_cvs_as_word_zip(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Andrea',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $profile = CvProfile::create([
            'user_id' => $user->id,
            'talent_id' => $talent->id,
            'title' => 'CV Andrea Lopez',
            'full_name' => 'Andrea Lopez',
            'language' => 'es',
            'summary' => 'Construye productos internos.',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('talents.download-cvs'), [
                'talent_ids' => [$talent->id],
                'cv_language' => 'es',
                'file_format' => 'word',
            ]);

        $response->assertOk();

        $zipPath = tempnam(sys_get_temp_dir(), 'cv-word-zip-');
        file_put_contents($zipPath, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertSame('cv-andrea-lopez.docx', $zip->getNameIndex(0));
        $docx = $zip->getFromIndex(0);
        $zip->close();
        @unlink($zipPath);

        $this->assertIsString($docx);
        $this->assertDocxContains($docx, $profile->full_name);
    }

    private function assertDocxContains(string $contents, string $expected): void
    {
        $this->assertStringContainsString($expected, $this->docxDocumentXml($contents));
    }

    private function docxDocumentXml(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cv-docx-test-');
        file_put_contents($path, $contents);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $document = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertIsString($document);

        return $document;
    }
}
