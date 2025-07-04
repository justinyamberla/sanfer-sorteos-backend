<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido')->unique();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('cantidad_boletos');
            $table->decimal('total', 10, 2);
            $table->enum('metodo_pago', ['tarjeta', 'transferencia', 'deposito']);
            $table->enum('estado', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->timestamp('fecha_pedido')->useCurrent();
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamp('fecha_expiracion')->nullable();
            $table->string('token_factura')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
