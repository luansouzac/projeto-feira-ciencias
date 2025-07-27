<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario; // Importa o Model Usuario
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Para hashing de senhas
use Illuminate\Support\Facades\Validator; // Para validação de dados de entrada

class UsuarioController extends Controller
{
    /**
     * Exibe uma lista de todos os usuários.
     *
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com todos os usuários.
     */
    public function index()
    {
        // Retorna todos os usuários existentes com status HTTP 200 OK.
        return response()->json(Usuario::with('tipoUsuario')->get(), 200);
    }

    /**
     * Armazena um novo usuário no banco de dados.
     *
     * @param  \Illuminate\Http\Request  $request Os dados da requisição para o novo usuário.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o usuário criado ou erros de validação.
     */
    public function store(Request $request)
    {
        // Define as regras de validação para a criação de um novo usuário.
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100',
            // O email deve ser único na tabela 'usuarios' (nome da tabela no banco de dados).
            'email' => 'required|string|email|max:100|unique:usuarios,email',
            'senha_hash' => 'required|string|min:6', // Senha mínima de 6 caracteres.
            // O id_tipo_usuario deve existir na tabela 'tipo_usuario' (nome da tabela no banco de dados).
            'id_tipo_usuario' => 'required|integer|exists:tipo_usuarios,id_tipo_usuario',
        ]);

        // Se a validação falhar, retorna os erros com status HTTP 422 Unprocessable Entity.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtém todos os dados da requisição.
        $data = $request->all();
        // Aplica o hash na senha antes de salvar por segurança.
        $data['senha_hash'] = Hash::make($request->senha_hash);

        // Cria um novo registro de usuário no banco de dados.
        $item = Usuario::create($data);

        // Retorna o usuário recém-criado com status HTTP 201 Created.
        return response()->json($item, 201);
    }

    /**
     * Exibe os detalhes de um usuário específico.
     *
     * @param  string  $id O ID do usuário a ser exibido.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o usuário encontrado ou um erro 404.
     */
    public function show(string $id)
    {
        // Tenta encontrar o usuário pelo ID.
        $item = Usuario::find($id);

        // Se o usuário for encontrado, retorna-o com status HTTP 200 OK.
        if ($item) {
            return response()->json($item, 200);
        }

        // Se o usuário não for encontrado, retorna um erro 404 Not Found.
        return response()->json(['erro' => 'Usuário não encontrado'], 404);
    }

    /**
     * Atualiza um usuário existente no banco de dados.
     *
     * @param  \Illuminate\Http\Request  $request Os dados da requisição para atualização.
     * @param  string  $id O ID do usuário a ser atualizado.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o usuário atualizado ou erros.
     */
    public function update(Request $request, string $id)
    {
        // Tenta encontrar o usuário a ser atualizado.
        $item = Usuario::find($id);

        // Se o usuário não for encontrado, retorna um erro 404 Not Found.
        if (!$item) {
            return response()->json(['erro' => 'Usuário não encontrado'], 404);
        }

        // Define as regras de validação para os dados de atualização.
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|string|max:100', // 'sometimes' indica que o campo é opcional na atualização.
            // O email deve ser único na tabela 'usuarios', exceto se for o email do próprio usuário que está sendo atualizado.
            'email' => 'sometimes|string|email|max:100|unique:usuarios,email,' . $id . ',id_usuario', // 'id_usuario' é a PK da sua tabela Usuario.
            'senha_hash' => 'sometimes|string|min:6',
            // O id_tipo_usuario deve existir na tabela 'tipo_usuario'.
            'id_tipo_usuario' => 'sometimes|integer|exists:tipo_usuario,id_tipo_usuario',
        ]);

        // Se a validação falhar, retorna os erros com status HTTP 422 Unprocessable Entity.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtém todos os dados da requisição.
        $data = $request->all();
        // Se uma nova senha_hash foi fornecida na requisição, aplique o hash.
        if (isset($data['senha_hash'])) {
            $data['senha_hash'] = Hash::make($request->senha_hash);
        }

        // Atualiza o registro do usuário no banco de dados.
        $item->update($data);

        // Retorna o usuário atualizado com status HTTP 200 OK.
        return response()->json($item, 200);
    }

    /**
     * Remove um usuário do banco de dados.
     *
     * @param  string  $id O ID do usuário a ser removido.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON indicando sucesso (204) ou erro (404).
     */
    public function destroy(string $id)
    {
        // Tenta encontrar o usuário a ser deletado.
        $item = Usuario::find($id);

        // Se o usuário for encontrado, o deleta.
        if ($item) {
            $item->delete();
            // Retorna status HTTP 204 No Content para indicar sucesso na exclusão sem retorno de corpo.
            return response()->json(null, 204);
        }

        // Se o usuário não for encontrado, retorna um erro 404 Not Found.
        return response()->json(['erro' => 'Usuário não encontrado'], 404);
    }
}
