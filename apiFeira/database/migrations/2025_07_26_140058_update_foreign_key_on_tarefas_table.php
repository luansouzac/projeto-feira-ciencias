<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Usamos Schema::table() para modificar uma tabela existente.
        Schema::table('tarefas', function (Blueprint $table) {
         
            $table->dropForeign('tarefas_id_projeto_foreign');

        
            $table->foreign('id_projeto')
                  ->references('id_projeto')
                  ->on('projetos')
                  ->onDelete('cascade'); // <-- A MÁGICA ACONTECE AQUI
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // O método down reverte o que o up() fez, para o caso de um rollback.
        Schema::table('tarefas', function (Blueprint $table) {
      
            $table->dropForeign('tarefas_id_projeto_foreign');

            $table->foreign('id_projeto')
                  ->references('id_projeto')
                  ->on('projetos');
        });
    }
};
