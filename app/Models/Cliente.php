<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'direccion',
        'ciudad',
        'provincia',
    ];

    public function boletos()
    {
        return $this->hasMany(Boleto::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}
