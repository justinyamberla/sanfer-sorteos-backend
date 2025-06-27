<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = [
        'cliente_id',
        'estado',
        'total' // ajusta según tu diseño
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function boletos()
    {
        return $this->hasMany(Boleto::class);
    }
}
