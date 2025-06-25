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
        Schema::create('comentario_desenvolvimentos', function (Blueprint $table) {
            $table->id('id_comentario'); 
            $table->unsignedBigInteger('id_registro');
            $table->unsignedBigInteger('id_orientador');
            $table->text('comentario');
            $table->dateTime('data_comentario')->nullable();

            $table->foreign('id_registro')->references('id_registro')->on('registro_tarefas')->onDelete('no action');

            $table->foreign('id_orientador')->references('id_usuario')->on('usuarios')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentario_desenvolvimentos');
    }
};
