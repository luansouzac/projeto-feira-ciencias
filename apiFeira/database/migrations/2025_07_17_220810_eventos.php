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
            $table->unsignedInteger('min_pessoas')->default(1);
            $table->unsignedInteger('max_pessoas');
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
            $table->dropColumn("min_pessoas");
            $table->dropColumn("max_pessoas");
        });
    }
};
