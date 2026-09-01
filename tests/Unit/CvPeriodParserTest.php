<?php

namespace Tests\Unit;

use App\Support\CvPeriodParser;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CvPeriodParserTest extends TestCase
{
    public function test_it_preserves_months_from_numeric_month_year_periods(): void
    {
        $dates = CvPeriodParser::dates('05/2022 - 07/2023', true);

        $this->assertSame('2022-05-01', $dates['start_date']);
        $this->assertSame('2023-07-31', $dates['end_date']);
        $this->assertFalse($dates['is_current']);
    }

    public function test_it_handles_compact_ranges_and_current_periods(): void
    {
        $dates = CvPeriodParser::dates('02/2024-03/2026', true);

        $this->assertSame('2024-02-01', $dates['start_date']);
        $this->assertSame('2026-03-31', $dates['end_date']);

        $current = CvPeriodParser::dates('julio 2025 - presente', true);

        $this->assertSame('2025-07-01', $current['start_date']);
        $this->assertNull($current['end_date']);
        $this->assertTrue($current['is_current']);
    }

    public function test_it_keeps_year_only_periods_as_year_boundaries(): void
    {
        $dates = CvPeriodParser::dates('2021 - 2023', true);

        $this->assertSame('2021-01-01', $dates['start_date']);
        $this->assertSame('2023-12-31', $dates['end_date']);
        $this->assertFalse($dates['is_current']);
    }

    public function test_it_formats_stored_dates_with_months_for_section_text(): void
    {
        $text = CvPeriodParser::textFromDates(
            Carbon::parse('2022-05-01'),
            Carbon::parse('2023-07-31'),
            false
        );

        $this->assertSame('05/2022 - 07/2023', $text);
    }
}
