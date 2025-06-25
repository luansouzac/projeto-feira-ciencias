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
        Schema::create('registro_tarefas', function (Blueprint $table) {
            $table->id('id_registro');
            $table->unsignedBigInteger('id_tarefa');
            $table->text('descricao_atividade');
            $table->text('resultado')->nullable();
            $table->dateTime('data_execucao')->nullable();
            $table->unsignedBigInteger('id_responsavel');

            $table->foreign('id_tarefa')->references('id_tarefa')->on('tarefas')->onDelete('no action');

            $table->foreign('id_responsavel')->references('id_usuario')->on('usuarios')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_tarefas');
    }
};
