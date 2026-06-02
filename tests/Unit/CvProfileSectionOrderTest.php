<?php

namespace Tests\Unit;

use App\Models\CvProfile;
use PHPUnit\Framework\TestCase;

class CvProfileSectionOrderTest extends TestCase
{
    public function test_normalized_section_order_removes_duplicates_and_restores_missing_sections(): void
    {
        $profile = new CvProfile([
            'section_order' => [
                'main' => ['experiences', 'education', 'experiences'],
                'side' => ['skills', 'skills', 'languages'],
            ],
        ]);

        $this->assertSame([
            'side' => ['skills', 'languages', 'software', 'certifications'],
            'main' => ['experiences', 'education'],
        ], $profile->normalizedSectionOrder());
    }
}
