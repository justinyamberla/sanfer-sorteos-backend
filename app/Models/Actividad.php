<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'fecha_sorteo',
        'boletos_generados',
        'boletos_vendidos',
        'boletos_ganadores',
        'estado',
        'url_live_sorteo',
        'precio_boleto',
    ];

    public function imagenes()
    {
        return $this->hasMany(ImagenActividad::class);
    }
}

