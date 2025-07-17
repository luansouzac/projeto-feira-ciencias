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
        Schema::table("eventos", function (Blueprint $table) {
            $table->date("data_evento")->nullable()->after("nome");
            $table->dateTime("inicio_submissao")->nullable()->after("data_evento");
            $table->dateTime("fim_submissao")->nullable()->after("inicio_submissao");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("eventos", function (Blueprint $table) {
            $table->dropColumn("data_evento");
            $table->dropColumn("inicio_submissao");
            $table->dropColumn("fim_submissao");
        });
    }
};