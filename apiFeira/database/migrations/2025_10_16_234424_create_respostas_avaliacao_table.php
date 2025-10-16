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
        Schema::create('respostas_avaliacao', function (Blueprint $table) {
            $table->id('id_resposta'); 
            $table->unsignedBigInteger('id_avaliacao');
            $table->unsignedBigInteger('id_pergunta');
            $table->integer('valor_resposta');
            $table->timestamps();

            $table->foreign('id_avaliacao')->references('id_avaliacao')->on('avaliacoes')->onDelete('cascade');
            $table->foreign('id_pergunta')->references('id_pergunta')->on('perguntas_questionario')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respostas_avaliacao');
    }
};