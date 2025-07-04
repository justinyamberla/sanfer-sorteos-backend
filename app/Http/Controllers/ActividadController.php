<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Boleto;
use App\Models\ImagenActividad;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        } catch (ModelNotFoundException $e) {
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

    public function ultimaActividadActiva()
    {
        try {
            $actividad = Actividad::with(['imagenes'])
                ->where('estado', 'activo')
                ->latest('created_at')
                ->first();

            if (!$actividad) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay actividades activas.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Última actividad activa obtenida correctamente.',
                'data' => $actividad
            ]);
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
            'nombre'            => 'required|string|min:10',
            'titulo'            => 'required|string|min:10',
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
                'nombre'            => $validated['nombre'],
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

            // Generar los boletos
            $totalBoletos = $validated['boletos_generados'];
            $ganadoresNecesarios = $validated['boletos_ganadores'];

            // Crear un array con todos los números de boleto
            $boletos = [];
            for ($i = 1; $i <= $totalBoletos; $i++) {
                $boletos[] = [
                    'numero'     => str_pad($i, 4, '0', STR_PAD_LEFT),
                    'es_ganador' => false
                ];
            }

            // Elegir boletos ganadores aleatorios
            $indicesGanadores = collect($boletos)
                ->keys()
                ->random($ganadoresNecesarios);

            foreach ($indicesGanadores as $index) {
                $boletos[$index]['es_ganador'] = true;
            }

            // Crear instancias para insertarlas
            $boletosDB = [];
            foreach ($boletos as $b) {
                $boletosDB[] = [
                    'actividad_id'     => $actividad->id,
                    'numero_boleto'    => $b['numero'],
                    'estado'           => 'disponible',
                    'es_ganador'       => $b['es_ganador'],
                    'created_at'       => now(),
                    'updated_at'       => now()
                ];
            }

            // Insertar todos los boletos
            Boleto::insert($boletosDB);

            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $index => $imagen) {
                    $uuid = (string) Str::uuid();

                    $extension = $imagen->extension(); // ej: jpg, png

                    // Guardar con nombre UUID.extension
                    $ruta = $imagen->storeAs(
                        "actividades/{$actividad->id}",
                        "{$uuid}.{$extension}",
                        'public'
                    );

                    $nombreOriginal = $imagen->getClientOriginalName();
                    $tamano  = $imagen->getSize();
                    $formato = $extension;

                    $imagenCreada = ImagenActividad::create([
                        'uuid'         => $uuid,
                        'actividad_id' => $actividad->id,
                        'nombre'       => $nombreOriginal,
                        'tamano'       => $tamano,
                        'formato'      => $formato,
                        'orden'        => $index + 1
                    ]);

                    $imagenesGuardadas[] = [
                        'ruta'   => $ruta,
                        'id'     => $imagenCreada->id,
                        'uuid'   => $uuid,
                        'nombre' => $nombreOriginal,
                        'tamano' => $tamano,
                        'formato'=> $formato,
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Actividad creada correctamente.',
                'data' => array_merge(
                    $actividad->toArray(),
                    ['imagenes' => $imagenesGuardadas]
                ),
            ], 201);

        } catch (\Throwable $e) {
            foreach ($imagenesGuardadas as $img) {
                if (isset($img['ruta'])) {
                    Storage::disk('public')->delete($img['ruta']);
                }
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
            'titulo'          => 'required|string|min:10',
            'descripcion'     => 'required|string|min:10',
            'fecha_sorteo'    => 'nullable|date',
            'url_live_sorteo' => 'nullable|url',
            'imagenes'        => 'required|array',
            'imagenes.*'      => 'image|mimes:jpeg,png,webp|max:2048'
        ]);

        $imagenesGuardadas = [];

        try {
            DB::beginTransaction();

            $actividad = Actividad::findOrFail($id);
            $actividad->update([
                'titulo'          => $validated['titulo'],
                'descripcion'     => $validated['descripcion'],
                'fecha_sorteo'    => $validated['fecha_sorteo'] ?? null,
                'url_live_sorteo' => $validated['url_live_sorteo'] ?? null,
            ]);

            // 1. Eliminar imágenes anteriores (físicamente + BD)
            foreach ($actividad->imagenes as $imagen) {
                Storage::disk('public')->delete("actividades/{$actividad->id}/{$imagen->uuid}.{$imagen->formato}");
                $imagen->delete();
            }

            // 2. Guardar nuevas imágenes (UUID, orden, etc)
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $index => $imagen) {
                    $uuid = (string) Str::uuid();
                    $extension = $imagen->extension();

                    $ruta = $imagen->storeAs(
                        "actividades/{$actividad->id}",
                        "{$uuid}.{$extension}",
                        'public'
                    );

                    $nombreOriginal = $imagen->getClientOriginalName();
                    $tamano = $imagen->getSize();
                    $formato = $extension;

                    $imagenCreada = ImagenActividad::create([
                        'uuid'         => $uuid,
                        'actividad_id' => $actividad->id,
                        'nombre'       => $nombreOriginal,
                        'tamano'       => $tamano,
                        'formato'      => $formato,
                        'orden'        => $index + 1
                    ]);

                    $imagenesGuardadas[] = [
                        'ruta'   => $ruta,
                        'id'     => $imagenCreada->id,
                        'uuid'   => $uuid,
                        'nombre' => $nombreOriginal,
                        'tamano' => $tamano,
                        'formato'=> $formato,
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Actividad actualizada correctamente.',
                'data' => array_merge(
                    $actividad->toArray(),
                    ['imagenes' => $imagenesGuardadas]
                )
            ]);
        } catch (\Throwable $e) {
            // Rollback: eliminar nuevas subidas si algo falla
            foreach ($imagenesGuardadas as $img) {
                if (isset($img['ruta'])) {
                    Storage::disk('public')->delete($img['ruta']);
                }
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

            // Cambiar estado a 'eliminado'
            $actividad->estado = 'eliminado';
            $actividad->save();

            // Eliminar imágenes físicas del storage
            $rutaCarpeta = "actividades/{$actividad->id}";
            if (Storage::disk('public')->exists($rutaCarpeta)) {
                Storage::disk('public')->deleteDirectory($rutaCarpeta);
            }

            // Eliminar registros de imágenes en la base de datos
            ImagenActividad::where('actividad_id', $actividad->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Actividad eliminada correctamente.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la actividad.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
