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
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id('id_avaliacao');
            $table->unsignedBigInteger('id_projeto');  
            $table->unsignedBigInteger('id_avaliador'); 
            $table->decimal('nota_geral', 5, 2);
            $table->text('observacoes');
            $table->timestamps(); 
            $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('cascade');
            $table->foreign('id_avaliador')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->unique(['id_projeto', 'id_avaliador']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
