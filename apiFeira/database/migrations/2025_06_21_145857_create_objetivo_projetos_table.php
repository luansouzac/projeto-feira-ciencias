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
        Schema::create('objetivo_projetos', function (Blueprint $table) {
            $table->id('id_objetivo');
            $table->unsignedBigInteger('id_projeto');
            $table->text('descricao');
            $table->foreign('id_projeto')->references('id_projeto')->on('projetos')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objetivo_projetos');
    }
};
