<?php

namespace Tests\Feature;

use App\Http\Requests\StoreCvEducationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CvEducationValidationTest extends TestCase
{
    public function test_end_date_is_allowed_without_start_date(): void
    {
        $request = StoreCvEducationRequest::create('/', 'POST', [
            'institution' => 'Universidad Nacional',
            'degree' => 'Licenciatura',
            'start_date' => '',
            'end_date' => '2024-06-01',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_end_date_must_not_be_before_start_date_when_start_date_is_present(): void
    {
        $request = StoreCvEducationRequest::create('/', 'POST', [
            'institution' => 'Universidad Nacional',
            'degree' => 'Licenciatura',
            'start_date' => '2024-06-01',
            'end_date' => '2020-06-01',
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }
}
