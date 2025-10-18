<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliador_projeto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_projeto');
            $table->unsignedBigInteger('id_avaliador');
            $table->enum('status', ['pendente', 'concluida'])->default('pendente');
            $table->timestamps();

            $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('cascade');
            $table->foreign('id_avaliador')->references('id_usuario')->on('usuarios')->onDelete('cascade');

            $table->unique(['id_projeto', 'id_avaliador']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliador_projeto');
    }
};
