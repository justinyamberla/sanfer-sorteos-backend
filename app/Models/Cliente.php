<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nombre', // Agrega los campos que vayas a usar
        'email',
        'telefono'
    ];

    public function boletos()
    {
        return $this->hasMany(Boleto::class);
    }
}
