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
        Schema::create('membro_equipes', function (Blueprint $table) {
            $table->id('id_membro');
            $table->unsignedBigInteger('id_equipe');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_funcao');
            $table->foreign('id_equipe')->references('id_equipe')->on('equipes')->onDelete('no action');//se apagar a equipe apaga o membrose equipe?
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('no action');
            $table->foreign('id_funcao')->references('id_funcao')->on('funcao')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membro_equipes');
    }
};
