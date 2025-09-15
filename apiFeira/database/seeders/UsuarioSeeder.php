<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Usuario::create([
            'nome' => 'Admin',
            'email' => 'admin@example.com',
            'senha_hash' => Hash::make('@admif123!#'),
            'data_cadastro' => '2025-07-16',
            'id_tipo_usuario' => 1,
            'id_matricula' => ''
        ]);
    }
}
