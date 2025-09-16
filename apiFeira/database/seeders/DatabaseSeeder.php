<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            TipoUsuarioSeeder::class,
            SituacaoSeeder::class,
            FuncaoSeeder::class,
            UsuarioSeeder::class,
            EquipeSeeder::class, // <-- AGORA ESTÁ NO LUGAR CERTO!
            RolesAndPermissionsSeeder::class, 
            // Adicione aqui todos os outros seeders que você criar para outras tabelas:
            // UsuarioSeeder::class,
            // ProjetoSeeder::class,
            // MembroEquipeSeeder::class,
            // etc.
        ]);
    }
}