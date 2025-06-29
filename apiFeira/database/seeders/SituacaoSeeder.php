<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Importe a Facade DB para interagir com o banco de dados

class SituacaoSeeder extends Seeder
{
    /**
     * Executa os seeds do banco de dados para a tabela 'situacao'.
     *
     * @return void
     */
    public function run()
    {
        // Define os tipos de situação a serem inseridos
        $situacoes = [
            ['situacao' => 'Em Análise'],
            ['situacao' => 'Aprovado'],
            ['situacao' => 'Reprovado'],
            ['situacao' => 'Reprovado com Ressalva'],
            // Você pode adicionar outras situações aqui, se necessário
        ];

        // Limpa a tabela 'situacao' antes de inserir, para evitar duplicatas em execuções repetidas.
        // CUIDADO: Isso apagará todos os dados existentes na tabela 'situacao'.
        // Se houver chaves estrangeiras que referenciam 'situacao' e você não está resetando o DB,
        // pode ser necessário desativar temporariamente as checagens de FK ou usar insertOrIgnore.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Desativa temporariamente as checagens de FK
        DB::table('situacao')->truncate(); // Limpa a tabela
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Ativa novamente as checagens de FK

        // Insere os dados na tabela 'situacao'.
        // Usamos insert() porque já limpamos a tabela com truncate().
        DB::table('situacao')->insert($situacoes);

        // Se preferir não limpar a tabela e apenas inserir se o registro não existir (exige campo 'situacao' UNIQUE)
        /*
        foreach ($situacoes as $situacao) {
            DB::table('situacao')->insertOrIgnore($situacao);
        }
        */
    }
}