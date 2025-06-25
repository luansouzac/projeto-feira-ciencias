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
        Schema::create('comentario_planejamentos', function (Blueprint $table) {
            $table->id('id_comentario');
            $table->unsignedBigInteger('id_projeto');
            $table->unsignedBigInteger('id_orientador');
            $table->text('comentario');
            $table->dateTime('data_comentario')->nullable();
            $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('no action');
            $table->foreign('id_orientador')->references('id_usuario')->on('usuarios')->onDelete('no action');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('comentario_planejamentos');
    }
};
