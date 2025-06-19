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
        Schema::create('projetos', function (Blueprint $table) {
            $table->id('id_projeto');
            $table->unsignedBigInteger('id_responsavel');
            $table->string('titulo', 200);
            $table->text('problema');
            $table->text('relevancia');
            $table->unsignedBigInteger('id_situacao');
            $table->dateTime('data_criacao')->nullable();
            $table->dateTime('data_aprovacao')->nullable();
            $table->foreign('id_responsavel')->references('id_usuario')->on('usuarios')->onDelete('no action');
            $table->foreign('id_situacao')->references('id_situacao')->on('situacao')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};
