<?php

namespace Database\Seeders;

use App\Models\TipoUsuario;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Usuario;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Limpar o cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        //Criar Permissões
        $permission = Permission::firstOrCreate(['name' => 'exibir projeto']);
        $permission = Permission::firstOrCreate(['name' => 'crud projeto']);
        $permission = Permission::firstOrCreate(['name' => 'exibir objetivo']);
        $permission = Permission::firstOrCreate(['name' => 'crud objetivo']);
        $permission = Permission::firstOrCreate(['name' => 'exibir equipe']);
        $permission = Permission::firstOrCreate(['name' => 'crud equipe']);
        $permission = Permission::firstOrCreate(['name' => 'exibir usuario']);
        $permission = Permission::firstOrCreate(['name' => 'crud usuario']);
        $permission = Permission::firstOrCreate(['name' => 'exibir tarefa']);
        $permission = Permission::firstOrCreate(['name' => 'crud tarefa']);
        $permission = Permission::firstOrCreate(['name' => 'exibir apresentacao']);
        $permission = Permission::firstOrCreate(['name' => 'crud apresentacao']);
        $permission = Permission::firstOrCreate(['name' => 'exibir evento']);
        $permission = Permission::firstOrCreate(['name' => 'crud evento']);
        $permission = Permission::firstOrCreate(['name' => 'exibir avaliacao']);
        $permission = Permission::firstOrCreate(['name' => 'crud avaliacao']);
        $permission = Permission::firstOrCreate(['name' => 'exibir comentario planejamento']);
        $permission = Permission::firstOrCreate(['name' => 'crud comentario planejamento']);
        $permission = Permission::firstOrCreate(['name' => 'exibir comentario desenvolvimento']);
        $permission = Permission::firstOrCreate(['name' => 'crud comentario desenvolvimento']);
        $permission = Permission::firstOrCreate(['name' => 'exibir discussao equipe']);
        $permission = Permission::firstOrCreate(['name' => 'crud discussao equipe']);
        $permission = Permission::firstOrCreate(['name' => 'exibir avaliacao projeto']);
        $permission = Permission::firstOrCreate(['name' => 'crud avaliacao projeto']);
        //Limpar o cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        //Criar Roles e Atribuir Permissões
        $role = Role::firstOrCreate(['name' => 'Administrador'])->givePermissionTo(Permission::all());
        #$role = Role::create(['name' => 'Aluno'])->givePermissionTo(['exibir projeto', 'crud projeto', 'crud objetivo', 'crud equipe', 'exibir usuario', 'crud tarefa', 'crud apresentacao', 'exibir evento', 'crud comentario planejamento', 'crud comentario desenvolvimento', 'crud discussao equipe', 'exibir avaliacao projeto']);
        $role = Role::firstOrCreate(['name' => 'Aluno'])->givePermissionTo(Permission::all());
        #$role = Role::create(['name' => 'Orientador'])->givePermissionTo(['crud comentario desenvolvimento','crud projeto', 'exibir objetivo', 'exibir equipe', 'crud usuario', 'crud tarefa', 'exibir apresentacao', 'exibir evento', 'exibir avaliacao', 'crud comentario planejamento', 'crud comentario desenvolvimento', 'exibir discussao equipe', 'crud avaliacao projeto']);
        $role = Role::firstOrCreate(['name' => 'Orientador'])->givePermissionTo(Permission::all());
        #$role = Role::create(['name' => 'Avaliador'])->givePermissionTo(['crud comentario desenvolvimento','exibir projeto', 'exibir objetivo', 'exibir equipe', 'exibir usuario', 'exibir tarefa', 'exibir apresentacao', 'exibir evento', 'crud avaliacao', 'exibir avaliacao projeto']);
        $role = Role::firstOrCreate(['name' => 'Avaliador'])->givePermissionTo(Permission::all());

        
        //Limpar o cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        //Associar o tipo do usuário ao papel
        $user = TipoUsuario::find(1);
        $user->assignRole('Administrador');
        
        $user = TipoUsuario::find(2);
        $user->assignRole('Aluno');
        
        $user = TipoUsuario::find(3);
        $user->assignRole('Avaliador');
        
        $user = TipoUsuario::find(4);
        $user->assignRole('Orientador');
    }
}