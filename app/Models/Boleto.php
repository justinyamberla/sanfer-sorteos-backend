<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boleto extends Model
{
    protected $fillable = [
        'actividad_id',
        'cliente_id',
        'pedido_id',
        'numero_boleto',
        'estado',
        'es_ganador',
        'fecha_asignacion'
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
