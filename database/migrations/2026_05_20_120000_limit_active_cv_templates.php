<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cv_templates')
            ->whereNotIn('slug', ['academico-bullet', 'act-digital'])
            ->update(['is_active' => false]);

        DB::table('cv_templates')
            ->whereIn('slug', ['academico-bullet', 'act-digital'])
            ->update(['is_active' => true]);
    }

    public function down(): void
    {
        DB::table('cv_templates')
            ->whereIn('slug', ['clasico-profesional', 'ejecutivo-premium', 'creativo-sidebar'])
            ->update(['is_active' => true]);
    }
};
