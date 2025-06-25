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
        Schema::create('atribuicao_tarefas', function (Blueprint $table) {
            $table->id('id_atribuicao');
            $table->unsignedBigInteger('id_tarefa');
            $table->unsignedBigInteger('id_membro');
            $table->date('data_atribuicao')->nullable();
            $table->foreign('id_tarefa')->references('id_tarefa')->on('tarefas')->onDelete('no action');
            $table->foreign('id_membro')->references('id_membro')->on('membro_equipes')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atribuicao_tarefas');
    }
};
