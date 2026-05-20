<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $actDigitalId = DB::table('cv_templates')
            ->where('slug', 'act-digital')
            ->value('id');

        if (! $actDigitalId) {
            return;
        }

        $allowedTemplateIds = DB::table('cv_templates')
            ->whereIn('slug', ['academico-bullet', 'act-digital'])
            ->pluck('id')
            ->all();

        DB::table('cv_profiles')
            ->whereNull('cv_template_id')
            ->orWhereNotIn('cv_template_id', $allowedTemplateIds)
            ->update(['cv_template_id' => $actDigitalId]);
    }

    public function down(): void
    {
        //
    }
};
