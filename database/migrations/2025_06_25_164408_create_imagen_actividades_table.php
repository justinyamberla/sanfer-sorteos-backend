<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagenes_actividad', function (Blueprint $table) {
            $table->uuid('uuid')->primary(); // UUID como clave primaria

            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->string('nombre')->nullable();
            $table->unsignedInteger('orden')->nullable();

            $table->unsignedBigInteger('tamano')->nullable(); // tamaño en bytes
            $table->string('formato')->nullable();            // por ejemplo: jpeg, png, webp

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes_actividad');
    }
};
