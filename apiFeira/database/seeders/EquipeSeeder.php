<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Projeto; // Importe o Model Projeto

class EquipeSeeder extends Seeder
{
    /**
     * Executa os seeds do banco de dados para a tabela 'equipes'.
     *
     * @return void
     */
    public function run()
    {
        // 1. Obter um ID de projeto existente.
        // É crucial que já existam projetos no seu DB, talvez criados por um ProjetoSeeder.
        $projetoId = Projeto::inRandomOrder()->first()?->id_projeto;

        // Se não houver projetos, você pode criar um simples ou lançar um erro.
        if (is_null($projetoId)) {
            // Se não houver projetos, crie um projeto dummy para associar a equipe.
            // Em um cenário real, você garantiria que ProjetoSeeder fosse executado antes.
            $projeto = Projeto::create([
                'id_responsavel' => 1, // Certifique-se que o usuário com ID 1 exista
                'titulo' => 'Projeto Dummy para Equipe Seeder',
                'problema' => 'Problema dummy',
                'relevancia' => 'Relevancia dummy',
                'id_situacao' => 1, // Certifique-se que a situação com ID 1 exista
                'data_criacao' => now(),
            ]);
            $projetoId = $projeto->id_projeto;
        }

        // Se, por algum motivo, ainda não tiver um projetoId, pare.
        if (is_null($projetoId)) {
            $this->command->info('Nenhum projeto encontrado ou criado para associar à equipe. Abortando EquipeSeeder.');
            return;
        }

        // Define os dados da equipe a serem inseridos
        $equipes = [
            ['id_projeto' => $projetoId], // Associa a equipe ao ID do projeto obtido
            // Adicione mais equipes se necessário, sempre associando a um id_projeto válido
            // ['id_projeto' => Projeto::inRandomOrder()->first()->id_projeto], // Para outra equipe com outro projeto
        ];

        // Limpa a tabela 'equipes' antes de inserir, para evitar duplicatas.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Desativa temporariamente as checagens de FK
        DB::table('equipes')->truncate(); // Limpa a tabela
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Ativa novamente as checagens de FK

        // Insere os dados na tabela 'equipes'.
        DB::table('equipes')->insert($equipes);

        $this->command->info('Equipes semeadas com sucesso para o projeto ID: ' . $projetoId);
    }
}