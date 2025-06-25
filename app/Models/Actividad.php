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
        'boletos_generados',
        'boletos_ganadores',
        'precio_boleto',
        'url_live_sorteo',
    ];

    public function imagenes()
    {
        return $this->hasMany(ImagenActividad::class);
    }
}
