<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagenActividad extends Model
{
    protected $table = 'imagenes_actividad';

    protected $fillable = [
        'actividad_id',
        'url',
        'nombre',
        'orden'
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }
}
