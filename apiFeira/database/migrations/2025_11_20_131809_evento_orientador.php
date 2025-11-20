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
        Schema::create('evento_orientador', function (Blueprint $table) {
            $table->id('id_evento_orientador');
            $table->unsignedBigInteger('id_evento');
            $table->unsignedBigInteger('id_orientador');
            $table->timestamps();

            $table->foreign('id_evento')->references('id_evento')->on('eventos')->onDelete('cascade');
            $table->foreign('id_orientador')->references('id_usuario')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_orientador');
    }
};
