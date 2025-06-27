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

    public function boletos()
    {
        return $this->hasMany(Boleto::class);
    }

    protected $appends = ['lista_boletos_ganadores'];

    public function getListaBoletosGanadoresAttribute()
    {
        return $this->boletos()
            ->where('es_ganador', true)
            ->pluck('numero_boleto'); // solo devuelve los números
    }
}

