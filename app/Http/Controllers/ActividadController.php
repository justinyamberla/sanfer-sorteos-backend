<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\ImagenActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ActividadController extends Controller
{
    public function index()
    {
        $actividades = Actividad::all();
        return response()->json([
            'success' => true,
            'data' => $actividades
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo'            => 'required|string|min:3',
            'descripcion'       => 'required|string|min:10',
            'fecha_inicio'      => 'required|date',
            'boletos_generados' => 'required|integer|min:1',
            'boletos_ganadores' => 'required|integer|min:1',
            'precio_boleto'     => 'required|numeric',
            'imagenes'          => 'required',
            'imagenes.*'        => 'image|mimes:jpeg,png,webp|max:2048'
        ]);

        $imagenesGuardadas = [];

        try {
            DB::beginTransaction();

            // Crear la actividad
            $actividad = Actividad::create([
                'titulo'            => $validated['titulo'],
                'descripcion'       => $validated['descripcion'],
                'fecha_inicio'      => $validated['fecha_inicio'],
                'fecha_fin'         => null,
                'fecha_sorteo'      => null,
                'boletos_generados' => $validated['boletos_generados'],
                'boletos_vendidos'  => 0,
                'boletos_ganadores' => $validated['boletos_ganadores'],
                'estado'            => 'activo',
                'url_live_sorteo'   => $validated['url_live_sorteo'] ?? null,
                'precio_boleto'     => $validated['precio_boleto']
            ]);

            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $ruta = $imagen->store("actividades/{$actividad->id}", 'public');
                    $nombre = $imagen->getClientOriginalName();

                    $imagenCreada = ImagenActividad::create([
                        'actividad_id' => $actividad->id,
                        'url'          => $ruta,
                        'nombre'       => $nombre,
                    ]);

                    $imagenesGuardadas[] = [
                        'id'     => $imagenCreada->id,
                        'nombre' => $nombre,
                        'url'    => $ruta,
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Actividad creada correctamente con imágenes.',
                'actividad' => array_merge(
                    $actividad->toArray(),
                    ['imagenes' => $imagenesGuardadas]
                ),
            ], 201);

        } catch (\Throwable $e) {
            // Eliminar archivos almacenados
            foreach ($imagenesGuardadas as $img) {
                Storage::disk('public')->delete($img['url']);
            }

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la actividad.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
