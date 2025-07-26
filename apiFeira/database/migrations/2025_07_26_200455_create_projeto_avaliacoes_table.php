<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('projeto_avaliacoes', function (Blueprint $table) {
        $table->id('id_projeto_avaliacao');

        $table->unsignedBigInteger('id_projeto');
        $table->unsignedBigInteger('id_avaliador');
        $table->unsignedBigInteger('id_situacao');

        $table->text('feedback')->nullable();
        $table->timestamps();

        $table->foreign('id_projeto')->references('id_projeto')->on('projetos');
        $table->foreign('id_avaliador')->references('id_usuario')->on('usuarios');
        $table->foreign('id_situacao')->references('id_situacao')->on('situacao');
    });
}

    public function down(): void
    {
        Schema::dropIfExists('projeto_avaliacoes');
    }
};