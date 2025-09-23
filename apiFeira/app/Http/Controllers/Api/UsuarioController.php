<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UsuarioController extends Controller
{
    // Listar todos os usuários (com filtro opcional por tipo)
    public function index(Request $request)
    {
        $query = Usuario::with('tipoUsuario');

        if ($request->has('id_tipo_usuario')) {
            $query->where('id_tipo_usuario', $request->input('id_tipo_usuario'));
        }
        
        return response()->json($query->get(), 200);
    }

    // Criar novo usuário
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:usuarios,email',
            'senha_hash' => 'required|string|min:6',
            'id_tipo_usuario' => 'required|integer|exists:tipo_usuarios,id_tipo_usuario',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['senha_hash'] = Hash::make($request->senha_hash);

        $item = Usuario::create($data);
        $item->load('tipoUsuario');

        return response()->json($item, 201);
    }

    // Buscar usuário por ID
    public function show(string $id)
    {
        $item = Usuario::with('tipoUsuario')->find($id);

        if ($item) {
            return response()->json($item, 200);
        }

        return response()->json(['erro' => 'Usuário não encontrado'], 404);
    }

    // Atualizar usuário por ID
    public function update(Request $request, string $id)
    {
        $item = Usuario::find($id);

        if (!$item) {
            return response()->json(['erro' => 'Usuário não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|string|max:100',
            'email' => 'sometimes|string|email|max:100|unique:usuarios,email,' . $id . ',id_usuario',
            'senha_hash' => 'sometimes|string|min:6',
            'id_tipo_usuario' => 'sometimes|integer|exists:tipo_usuarios,id_tipo_usuario',
            'telefone' => 'sometimes|string|max:20',
            'ano' => 'sometimes|string|max:50',
            'photo' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        if (isset($data['senha_hash'])) {
            $data['senha_hash'] = Hash::make($request->senha_hash);
        }

        $item->update($data);
        $item->load('tipoUsuario');

        return response()->json($item, 200);
    }

    // Deletar usuário por ID
    public function destroy(string $id)
    {
        $item = Usuario::find($id);

        if ($item) {
            $item->delete();
            return response()->json(null, 204);
        }

        return response()->json(['erro' => 'Usuário não encontrado'], 404);
    }

    // Retornar dados do usuário logado
    public function me()
    {
        return response()->json(Auth::user());
    }

    // Atualizar dados do usuário logado
    public function updateMe(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'telefone' => 'nullable|string|max:20',
            'ano' => 'nullable|string|max:50',
            'photo' => 'nullable|string',
        ]);

        $user->update($request->only(['telefone', 'ano', 'photo']));

        return response()->json($user);
    }

    public function inserirLista(Request $request)
    {
        // 1. Validação dos dados
        $request->validate([
            'dados' => 'required|array',
            'dados.*.nome' => 'required|string|max:255',
            'dados.*.email' => 'required|string|email|max:100|unique:usuarios,email',
            'dados.*.senha_hash' => 'sometimes|string|min:6',
            'dados.*.id_tipo_usuario' => 'sometimes|integer|exists:tipo_usuarios,id_tipo_usuario',
        ]);

        // 2. Coleta dos dados
        $listaDeDados = $request->input('dados');

        // 3. Preparação dos dados
        // Opcional: Adicionar timestamps (created_at e updated_at)
        $agora = now();
        $dadosParaInserir = collect($listaDeDados)->map(function ($item) use ($agora) {
            $item["senha_hash"] = Hash::make($item["senha_hash"]);
            return array_merge($item, [
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        })->all();

        try {
            foreach ($dadosParaInserir as $dados){
                Usuario::create($dados);
            }
            return response()->json(['mensagem' => 'Dados inseridos com sucesso!'], 201);
        } catch (\Exception $e) {
            return response()->json(['erro' => 'Não foi possível inserir os dados.', 'detalhes' => $e->getMessage()], 500);
        }
    }
}
