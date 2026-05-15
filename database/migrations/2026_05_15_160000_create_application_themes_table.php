<?php

use App\Models\ApplicationTheme;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('light_palette');
            $table->json('dark_palette');
            $table->string('background_image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        ApplicationTheme::ensureDefaultThemes();
    }

    public function down(): void
    {
        Schema::dropIfExists('application_themes');
    }
};
