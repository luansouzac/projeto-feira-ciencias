<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('avaliacoes');

        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id('id_avaliacao');
            
            $table->unsignedBigInteger('id_avaliador_projeto'); 
            
            $table->decimal('nota_geral', 5, 2);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('id_avaliador_projeto')->references('id')->on('avaliador_projeto')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
