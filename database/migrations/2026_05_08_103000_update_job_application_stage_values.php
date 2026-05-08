<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('job_applications')->where('stage', 'screening')->update(['stage' => 'review']);
        DB::table('job_applications')->where('stage', 'interview')->update(['stage' => 'technical_interview']);
        DB::table('job_applications')->where('stage', 'technical_test')->update(['stage' => 'psychometric_tests']);
        DB::table('job_applications')->where('stage', 'offer')->update(['stage' => 'offer_sent']);
    }

    public function down(): void
    {
        DB::table('job_applications')->where('stage', 'review')->update(['stage' => 'screening']);
        DB::table('job_applications')->where('stage', 'technical_interview')->update(['stage' => 'interview']);
        DB::table('job_applications')->where('stage', 'psychometric_tests')->update(['stage' => 'technical_test']);
        DB::table('job_applications')->where('stage', 'offer_sent')->update(['stage' => 'offer']);
    }
};
