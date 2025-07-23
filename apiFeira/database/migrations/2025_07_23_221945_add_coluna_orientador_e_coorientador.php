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
        Schema::table('projetos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_orientador')->nullable();
            $table->unsignedBigInteger('id_coorientador')->nullable();

            $table->foreign('id_orientador')->references('id_usuario')->on('usuarios')->onDelete('no action');
            $table->foreign('id_coorientador')->references('id_usuario')->on('usuarios')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropForeign(['id_orientador']);
            $table->dropForeign(['id_coorientador']);

            $table->dropColumn('id_orientador');
            $table->dropColumn('id_coorientador');
        });
    }
};
