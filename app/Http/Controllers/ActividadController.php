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
        $actividades = Actividad::with('imagenes')->where('estado', 'activo')->get();

        return response()->json([
            'success' => true,
            'message' => 'Actividades obtenidas correctamente.',
            'data' => $actividades
        ]);
    }

    public function show($id)
    {
        try {
            $actividad = Actividad::with('imagenes')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Actividad obtenida correctamente.',
                'data' => $actividad,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Actividad no encontrada.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la actividad.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                'data' => array_merge(
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

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'titulo'            => 'sometimes|required|string|min:3',
            'descripcion'       => 'sometimes|required|string|min:10',
            'fecha_inicio'      => 'sometimes|required|date',
            'boletos_generados' => 'sometimes|required|integer|min:1',
            'boletos_ganadores' => 'sometimes|required|integer|min:1',
            'precio_boleto'     => 'sometimes|required|numeric',
            'imagenes'          => 'sometimes|array',
            'imagenes.*'        => 'image|mimes:jpeg,png,webp|max:2048'
        ]);

        $imagenesGuardadas = [];

        try {
            DB::beginTransaction();

            $actividad = Actividad::with('imagenes')->findOrFail($id);

            // Actualizar solo los campos enviados
            $actividad->fill($validated);
            $actividad->save();

            // Si se suben nuevas imágenes, eliminar anteriores y guardar nuevas
            if ($request->hasFile('imagenes')) {
                // Eliminar imágenes anteriores (BD y disco)
                foreach ($actividad->imagenes as $img) {
                    Storage::disk('public')->delete($img->url);
                    $img->delete();
                }

                // Guardar nuevas imágenes
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
                'message' => 'Actividad actualizada correctamente.',
                'data' => array_merge(
                    $actividad->toArray(),
                    ['imagenes' => $imagenesGuardadas ?: $actividad->imagenes->toArray()]
                ),
            ], 200);

        } catch (\Throwable $e) {
            // Si falló algo y se guardaron imágenes nuevas, las eliminamos
            foreach ($imagenesGuardadas as $img) {
                Storage::disk('public')->delete($img['url']);
            }

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la actividad.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $actividad = Actividad::findOrFail($id);
            $actividad->estado = 'eliminado';
            $actividad->save();

            return response()->json([
                'success' => true,
                'message' => 'Actividad eliminada correctamente.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la actividad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
