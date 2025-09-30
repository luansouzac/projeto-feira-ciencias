<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuário Administrador
        Usuario::create([
            'nome' => 'Admin',
            'email' => 'admin@example.com',
            'senha_hash' => Hash::make('@admif123!#'),
            'data_cadastro' => Carbon::now(),
            'id_tipo_usuario' => 1, // Supondo que 1 = Admin
            'id_matricula' => 'ADM001'
        ]);

        // Usuários Professores
        Usuario::create([
            'nome' => 'Prof. Carlos Andrade',
            'email' => 'carlos.andrade@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now(),
            'id_tipo_usuario' => 4, // Supondo que 4 = Professor
            'id_matricula' => 'P10234'
        ]);

        Usuario::create([
            'nome' => 'Profa. Ana Beatriz',
            'email' => 'ana.beatriz@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(5),
            'id_tipo_usuario' => 4, // Supondo que 4 = Professor
            'id_matricula' => 'P10235'
        ]);

        Usuario::create([
            'nome' => 'Prof. Ricardo Souza',
            'email' => 'ricardo.souza@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(8),
            'id_tipo_usuario' => 4, // Supondo que 4 = Professor
            'id_matricula' => 'P10236'
        ]);

        Usuario::create([
            'nome' => 'Profa. Juliana Lima',
            'email' => 'juliana.lima@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(9),
            'id_tipo_usuario' => 4, // Supondo que 4 = Professor
            'id_matricula' => 'P10237'
        ]);

        // Usuários Alunos
        Usuario::create([
            'nome' => 'Maria Eduarda Silva',
            'email' => 'maria.silva@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(10),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024001'
        ]);

        Usuario::create([
            'nome' => 'João Pedro Martins',
            'email' => 'joao.martins@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(12),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024002'
        ]);

        Usuario::create([
            'nome' => 'Laura Costa',
            'email' => 'laura.costa@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(15),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024003'
        ]);

        Usuario::create([
            'nome' => 'Lucas Ferreira',
            'email' => 'lucas.ferreira@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(20),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024004'
        ]);

        Usuario::create([
            'nome' => 'Beatriz Oliveira',
            'email' => 'beatriz.oliveira@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(22),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024005'
        ]);

        Usuario::create([
            'nome' => 'Gabriel Santos',
            'email' => 'gabriel.santos@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(25),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024006'
        ]);

        Usuario::create([
            'nome' => 'Sofia Pereira',
            'email' => 'sofia.pereira@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(28),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024007'
        ]);

        Usuario::create([
            'nome' => 'Matheus Rodrigues',
            'email' => 'matheus.rodrigues@example.com',
            'senha_hash' => Hash::make('senha123'),
            'data_cadastro' => Carbon::now()->subDays(30),
            'id_tipo_usuario' => 2, // Supondo que 2 = Aluno
            'id_matricula' => 'A2024008'
        ]);
    }
}

