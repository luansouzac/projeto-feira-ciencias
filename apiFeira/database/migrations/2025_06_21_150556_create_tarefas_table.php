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
        Schema::create('tarefas', function (Blueprint $table) {
            $table->id('id_tarefa');
            $table->unsignedBigInteger('id_projeto');
            $table->text('descricao');
            $table->text('detalhe')->nullable();
            $table->unsignedBigInteger('id_situacao');
            $table->date('data_inicio_prevista')->nullable();
            $table->date('data_fim_prevista')->nullable();
            $table->date('data_conclusao')->nullable();//adicionei data_conclusao
            $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('no action');
            $table->foreign('id_situacao')->references('id_situacao')->on('situacao')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarefas');
    }
};
