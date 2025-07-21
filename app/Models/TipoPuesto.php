<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPuesto extends Model
{
        protected $fillable = [
        'id_usuario',
        'nombre',
    ];

        public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }


    public function candidatos()
    {
        return $this->hasMany(Candidato::class, 'tipo_puesto_id');
    }
}
