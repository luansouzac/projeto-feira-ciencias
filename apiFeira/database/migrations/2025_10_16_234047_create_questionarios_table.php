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
        Schema::create('questionarios', function (Blueprint $table) {
            $table->id('id_questionario');
            $table->unsignedBigInteger('id_evento');
            $table->string('titulo');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('id_evento')->references('id_evento')->on('eventos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionarios');
    }
};
