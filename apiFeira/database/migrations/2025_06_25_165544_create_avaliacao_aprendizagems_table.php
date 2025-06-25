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
    Schema::create('avaliacao_aprendizagens', function (Blueprint $table) {
        $table->id('id_avaliacao'); 
        $table->unsignedBigInteger('id_projeto');
        $table->unsignedBigInteger('id_avaliador');
        $table->decimal('nota', 5, 2)->nullable();
        $table->text('comentario')->nullable();
        $table->dateTime('data_avaliacao')->nullable();

        $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('no action');

        $table->foreign('id_avaliador')->references('id_usuario')->on('usuarios')->onDelete('no action');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacao_aprendizagems');
    }
};
