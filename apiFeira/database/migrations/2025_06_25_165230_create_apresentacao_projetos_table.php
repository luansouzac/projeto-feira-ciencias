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
    Schema::create('apresentacao_projetos', function (Blueprint $table) {
        $table->id('id_apresentacao');
        $table->unsignedBigInteger('id_projeto');
        $table->string('arquivo_pdf', 255);
        $table->dateTime('data_envio')->nullable();

        $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('no action');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apresentacao_projetos');
    }
};
