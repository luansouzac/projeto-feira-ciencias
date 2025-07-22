<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Usuario;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up(): void
    {
        // Resetar o cache de permissões para garantir que tudo seja lido do zero
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $tiposDeUsuario = DB::table('tipo_usuarios')->get();

        foreach ($tiposDeUsuario as $tipo) {
            $role = Role::findOrCreate($tipo->tipo, 'web');
            echo "Role '{$tipo->tipo}' encontrada ou criada.\n";

            // O resto do código continua igual
            Usuario::where('id_tipo_usuario', $tipo->id_tipo_usuario)->cursor()->each(function (Usuario $user) use ($role) {
                // O método assignRole é inteligente e não atribui a role duas vezes.
                $user->assignRole($role);
            });
            echo "Role '{$tipo->tipo}' garantida para os usuários correspondentes.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
     
        $tiposDeUsuario = DB::table('tipo_usuarios')->get();
        foreach ($tiposDeUsuario as $tipo) {
            $role = Role::where('name', $tipo->tipo)->first();
            if ($role) {
                Usuario::role($role->name)->cursor()->each(function (Usuario $user) use ($role) {
                    $user->removeRole($role);
                });
                $role->delete();
            }
        }
    }
};