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
        Schema::create('tarefa_feedback', function (Blueprint $table) {
            $table->id('id_tarefa_feedback');
            $table->unsignedBigInteger('id_tarefa');
            $table->unsignedBigInteger('id_usuario');
            $table->text('feedback');
            $table->foreign('id_tarefa')->references('id_tarefa')->on('tarefas')->onDelete('no action');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('no action');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarefa_feedback');
    }
};
