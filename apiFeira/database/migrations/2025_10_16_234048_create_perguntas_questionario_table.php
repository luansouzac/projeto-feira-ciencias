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
        Schema::create('perguntas_questionario', function (Blueprint $table) {
            $table->id('id_pergunta'); 
            $table->unsignedBigInteger('id_questionario');
            $table->string('criterio');
            $table->text('texto_pergunta');
            $table->integer('ordem')->default(0);
            $table->timestamps();

            $table->foreign('id_questionario')->references('id_questionario')->on('questionarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perguntas_questionario');
    }
};
