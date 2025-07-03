<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImagenActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ImagenActividadController extends Controller
{
    public function verImagen($actividad_id, $uuid, $extension)
    {
        $imagen = ImagenActividad::where('uuid', $uuid)
            ->where('actividad_id', $actividad_id)
            ->firstOrFail();

        $ruta = "actividades/{$actividad_id}/{$uuid}.{$imagen->formato}";

        if (!Storage::disk('public')->exists($ruta)) {
            abort(404, 'Imagen no encontrada');
        }

        $file = Storage::disk('public')->get($ruta);
        $mimeType = Storage::disk('public')->mimeType($ruta);

        return Response::make($file, 200, [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
