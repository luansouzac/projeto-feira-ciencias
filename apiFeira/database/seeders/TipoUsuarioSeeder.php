<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Importe a Facade DB para interagir com o banco de dados

class TipoUsuarioSeeder extends Seeder
{
    /**
     * Executa os seeds do banco de dados.
     *
     * @return void
     */
    public function run()
    {

        // Dados a serem inseridos na tabela tipo_usuario
        $tipos = [
            ['tipo' => 'Administrador'],
            ['tipo' => 'Aluno'],
            ['tipo' => 'Avaliador'], 
            ['tipo' => 'Orientador'], 
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipo_usuarios')->insertOrIgnore($tipo); // insertOrIgnore evita duplicatas se o 'tipo' for único
        }

       
    }
}