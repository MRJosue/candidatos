<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidato extends Model
{
    use HasFactory;

    // Habilitamos asignación masiva para estos campos
    protected $fillable = [
        'id_usuario',
        'tipo_puesto_id',
        'nombre',
        'correo',
        'telefono',
        'fecha_postulacion',
        'comentarios',
    ];

    /**
     * Relación: el usuario que creó el candidato
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    /**
     * Relación: el tipo de puesto al que postula el candidato
     */
    public function tipoPuesto()
    {
        return $this->belongsTo(TipoPuesto::class, 'tipo_puesto_id');
    }
}
