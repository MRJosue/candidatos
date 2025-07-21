<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('candidatos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_usuario')->constrained('users');
        $table->foreignId('tipo_puesto_id')->constrained('tipo_puestos');
        $table->string('nombre');
        $table->string('correo');
        $table->string('telefono');
        $table->date('fecha_postulacion');
        $table->text('comentarios')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};
