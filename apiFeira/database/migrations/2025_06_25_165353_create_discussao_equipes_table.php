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
    Schema::create('discussao_equipes', function (Blueprint $table) {
        $table->id('id_discussao');
        $table->unsignedBigInteger('id_projeto');
        $table->text('pontos_fortes')->nullable();
        $table->text('pontos_fracos')->nullable();
        $table->text('trabalhos_futuros')->nullable();
        $table->dateTime('data_registro')->nullable();

        $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('no action');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discussao_equipes');
    }
};
