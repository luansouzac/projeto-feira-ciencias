<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents; // Esta linha pode ser removida se não estiver usando
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
        // Chame todos os seus seeders em um ÚNICO array
        $this->call([
            TipoUsuarioSeeder::class,
            SituacaoSeeder::class,
            FuncaoSeeder::class,
            UsuarioSeeder::class,
            EquipeSeeder::class, // <-- AGORA ESTÁ NO LUGAR CERTO!
            // Adicione aqui todos os outros seeders que você criar para outras tabelas:
            // UsuarioSeeder::class,
            // ProjetoSeeder::class,
            // MembroEquipeSeeder::class,
            // etc.
        ]);
    }
}