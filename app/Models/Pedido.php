<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = [
        'numero_pedido',
        'actividad_id',
        'cliente_id',
        'cantidad_boletos',
        'total',
        'metodo_pago',
        'estado',
        'fecha_pedido',
        'fecha_pago',
        'fecha_expiracion',
        'token_factura',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }

    public function boletos()
    {
        return $this->hasMany(Boleto::class);
    }
}
