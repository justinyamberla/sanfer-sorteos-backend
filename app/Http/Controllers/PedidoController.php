<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Boleto;
use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    public function store(Request $request) {
        $validated = $request->validate([
            'cliente.nombres' => 'required|string',
            'cliente.apellidos' => 'required|string',
            'cliente.email' => 'required|email',
            'cliente.telefono' => 'required|string',
            'cliente.direccion' => 'nullable|string',
            'cliente.ciudad' => 'nullable|string',
            'cliente.provincia' => 'nullable|string',

            'pedido.actividad_id' => 'required|exists:actividades,id',
            'pedido.cantidad' => 'required|integer|min:1',
            'pedido.total' => 'required|numeric|min:0',
            'pedido.metodoPago' => 'required|in:offline',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido creado con éxito',
            'data' => [
                'cliente' => $validated['cliente'],
                'pedido' => $validated['pedido'],
            ],
        ]);
    }

    public function porActividad($id)
    {
        $paginacion = Pedido::with([
            'cliente:id,nombres,apellidos,email,telefono',
            'boletos:id,pedido_id,numero_boleto,es_ganador'
        ])
            ->where('actividad_id', $id)
            ->orderByDesc('created_at')
            ->paginate(10);

        if ($paginacion->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron pedidos para esta actividad',
                'pedidos' => [],
            ], 404);
        }

        $pedidos = $paginacion->map(function ($pedido) {
            $pedido->producto = 'Números ' . $pedido->actividad->nombre . ' - ' . $pedido->actividad->titulo;
            return $pedido;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pedidos obtenidos correctamente',
            'pedidos' => $pedidos, // solo los elementos actuales
            'pagination' => [
                'current_page' => $paginacion->currentPage(),
                'last_page' => $paginacion->lastPage(),
                'per_page' => $paginacion->perPage(),
                'total' => $paginacion->total()
            ]
        ]);
    }

    public function porActividadActiva()
    {
        $actividad = Actividad::where('estado', 'activo')->first();

        if (!$actividad) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una actividad activa actualmente',
                'pedidos' => [],
            ], 404);
        }

        $paginacion = Pedido::with([
            'cliente:id,nombres,apellidos,email,telefono',
            'boletos:id,pedido_id,numero_boleto,es_ganador',
            'actividad:id,nombre,titulo'
        ])
            ->where('actividad_id', $actividad->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $pedidos = $paginacion->map(function ($pedido) {
            $pedido->producto = 'Números ' . $pedido->actividad->nombre . ' - ' . $pedido->actividad->titulo;
            return $pedido;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pedidos de la actividad activa obtenidos correctamente',
            'pedidos' => $pedidos,
            'pagination' => [
                'current_page' => $paginacion->currentPage(),
                'last_page' => $paginacion->lastPage(),
                'per_page' => $paginacion->perPage(),
                'total' => $paginacion->total()
            ]
        ]);
    }

    public function storeOffline(Request $request)
    {
        $validated = $request->validate([
            'cliente.nombres' => 'required|string',
            'cliente.apellidos' => 'required|string',
            'cliente.email' => 'required|email',
            'cliente.telefono' => 'required|string',
            'cliente.direccion' => 'nullable|string',
            'cliente.ciudad' => 'nullable|string',
            'cliente.provincia' => 'nullable|string',
            'cliente.recibirNotificaciones' => 'required|boolean',

            'pedido.actividad_id' => 'required|exists:actividades,id',
            'pedido.cantidad' => 'required|integer|min:1',
            'pedido.total' => 'required|numeric|min:0',
            'pedido.metodoPago' => 'required|in:offline',
        ]);

        DB::beginTransaction();

        try {
            // 1. Crear cliente
            $cliente = Cliente::create($validated['cliente']);

            // 2. Obtener actividad
            $actividad = Actividad::findOrFail($validated['pedido']['actividad_id']);
            $cantidad = $validated['pedido']['cantidad'];

            // 3. Obtener boletos disponibles aleatorios
            $boletosDisponibles = Boleto::where('actividad_id', $actividad->id)
                ->where('estado', 'disponible')
                ->lockForUpdate()
                ->inRandomOrder()
                ->limit($cantidad)
                ->get();

            if ($boletosDisponibles->count() < $cantidad) {
                throw new \Exception("No hay suficientes boletos disponibles.");
            }

            // 4. Generar número de pedido incremental
            $ultimoNumero = Pedido::max('numero_pedido') ?? 1000; // inicia en 1001 si está vacío
            $nuevoNumero = $ultimoNumero + 1;

            // 5. Crear el pedido
            $pedido = Pedido::create([
                'numero_pedido' => $nuevoNumero,
                'actividad_id' => $actividad->id,
                'cliente_id' => $cliente->id,
                'cantidad_boletos' => $cantidad,
                'total' => $validated['pedido']['total'],
                'metodo_pago' => $validated['pedido']['metodoPago'],
                'estado' => 'pendiente',
                'fecha_pedido' => now(),
                'fecha_expiracion' => now()->addHours(24),
            ]);

            // 6. Asignar boletos al pedido
            foreach ($boletosDisponibles as $boleto) {
                $boleto->update([
                    'cliente_id' => $cliente->id,
                    'pedido_id' => $pedido->id,
                    'estado' => 'reservado',
                    'fecha_asignacion' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado con éxito',
                'data' => [
                    'pedido' => $pedido,
                    'cliente' => $cliente,
                    'boletos' => $boletosDisponibles,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function actualizarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,pagado,cancelado'
        ]);

        $pedido = Pedido::with('boletos')->findOrFail($id);
        $nuevoEstado = $request->estado;

        // Si se cancela, liberar los boletos
        if ($nuevoEstado === 'cancelado' && $pedido->estado !== 'cancelado') {
            foreach ($pedido->boletos as $boleto) {
                $boleto->update([
                    'estado' => 'disponible',
                    'cliente_id' => null,
                    'pedido_id' => null,
                    'fecha_asignacion' => null,
                ]);
            }
        }

        // Actualizar estado
        $pedido->estado = $nuevoEstado;

        // Si se marca como pagado, registra la fecha de pago
        if ($nuevoEstado === 'pagado' && !$pedido->fecha_pago) {
            $pedido->fecha_pago = now();
        }

        $pedido->save();

        return response()->json([
            'success' => true,
            'message' => "Pedido actualizado correctamente.",
            'data' => $pedido
        ]);
    }

}
