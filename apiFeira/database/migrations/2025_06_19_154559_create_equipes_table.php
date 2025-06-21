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
        Schema::create('equipes', function (Blueprint $table) {
            $table->id('id_equipe');
            $table->unsignedBigInteger('id_projeto');
            $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('no action');// ver se precisa deletar o projeto se a equipe for apagada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipes');
    }
};
