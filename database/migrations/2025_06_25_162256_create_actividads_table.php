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
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->date('fecha_sorteo')->nullable();
            $table->integer('boletos_generados');
            $table->integer('boletos_vendidos')->default(0);
            $table->integer('boletos_ganadores');
            $table->enum('estado', ['activo', 'inactivo', 'finalizado'])->default('activo');
            $table->string('url_live_sorteo')->nullable();
            $table->decimal('precio_boleto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
