<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagenActividad extends Model
{
    protected $table = 'imagenes_actividad';

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'actividad_id',
        'nombre',
        'orden',
        'tamano',
        'formato',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return url("media/actividades/{$this->actividad_id}/{$this->uuid}.{$this->formato}");
    }
}
