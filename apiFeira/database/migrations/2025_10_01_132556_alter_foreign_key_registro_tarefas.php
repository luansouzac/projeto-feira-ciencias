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
        Schema::table('registro_tarefas', function (Blueprint $table) {
            // 1. Remove a chave estrangeira existente
            $table->dropForeign(['id_tarefa']);

            // 2. Cria a nova chave estrangeira com onDelete('cascade')
            $table->foreign('id_tarefa')
                  ->references('id_tarefa') // Coluna referenciada
                  ->on('tarefas') // Tabela referenciada
                  ->onDelete('cascade') // Ação desejada
                  ->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_tarefas', function (Blueprint $table) {
            //
        });
    }
};
