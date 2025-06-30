<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class FuncaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Define os tipos de função a serem inseridos
        $funcoes = [
            ['funcao' => 'Líder'],
            ['funcao' => 'Membro'],
            // Adicione outras funções aqui, se necessário (ex: 'Colaborador', 'Estagiário')
        ];
        // Limpa a tabela 'funcao' antes de inserir, para evitar duplicatas em execuções repetidas.
        // CUIDADO: Isso apagará todos os dados existentes na tabela 'funcao'.
        // Se houver chaves estrangeiras que referenciam 'funcao' e você não está resetando o DB,
        // pode ser necessário desativar temporariamente as checagens de FK ou usar insertOrIgnore.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Desativa temporariamente as checagens de FK
        DB::table('funcao')->truncate(); // Limpa a tabela
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Ativa novamente as checagens de FK

        // Insere os dados na tabela 'funcao'.
        // Usamos insert() porque já limpamos a tabela com truncate().
        DB::table('funcao')->insert($funcoes);
        
    }
}
