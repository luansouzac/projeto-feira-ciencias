<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('id_matricula', 100)->after('id_tipo_usuario');
            $table->string('telefone', 20)->nullable()->after('id_matricula');
            $table->string('ano', 50)->nullable()->after('telefone');
            $table->string('photo')->nullable()->after('ano'); // pode guardar o path da imagem
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['telefone', 'ano', 'photo']);
        });
    }
};
