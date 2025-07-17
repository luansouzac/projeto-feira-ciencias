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
        Schema::create('evento_projeto', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
    
            $table->unsignedInteger('min_pessoas')->default(1);
            $table->unsignedInteger('max_pessoas');

            $table->foreignId('id_evento')->constrained()->onDelete('no action');
            $table->foreignId('id_projeto')->constrained()->onDelete('no action');
        
            $table->unique(['evento_id', 'id_projeto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_projeto');
    }
};
