<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            TipoUsuarioSeeder::class, // Adicione esta linha
            // Outros seeders que você possa ter no futuro, por exemplo:
            // UsuarioSeeder::class,
            // ProjetoSeeder::class,
        ]);
    }
}