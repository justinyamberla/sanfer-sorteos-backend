<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('boletos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();

            $table->string('numero_boleto')->unique(); // Ej: 0001, 0045, etc.
            $table->enum('estado', ['disponible', 'reservado', 'asignado'])->default('disponible');
            $table->boolean('es_ganador')->default(false);
            $table->timestamp('fecha_asignacion')->nullable();

            $table->timestamps();

            // Claves foráneas
            $table->foreign('actividad_id')->references('id')->on('actividades')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
            $table->foreign('pedido_id')->references('id')->on('pedidos')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('boletos');
    }
};
