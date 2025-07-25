<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projeto; // Importa a Model Projeto (sua versão)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // Para validação de dados de entrada
use Illuminate\Validation\Rule;

class ProjetoController extends Controller
{
    /**
     * Exibe uma lista de todos os projetos.
     *
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com todos os projetos.
     */
    public function index()
    {
        // Retorna todos os projetos existentes com status HTTP 200 OK.
        return response()->json(Projeto::all(), 200);
    }

    /**
     * Armazena um novo projeto no banco de dados.
     *
     * @param  \Illuminate\Http\Request  $request Os dados da requisição para o novo projeto.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o projeto criado ou erros de validação.
     */
    public function store(Request $request)
    {
        // Define as regras de validação para a criação de um novo projeto.
        // As tabelas para 'exists' são baseadas nos nomes das tabelas do seu DB.
        $validator = Validator::make($request->all(), [
            'id_responsavel' => 'required|integer|exists:usuarios,id_usuario', // Garante que o responsável existe na tabela 'usuarios'
            'titulo' => 'required|string|max:200',
            'problema' => 'required|string',
            'relevancia' => 'required|string',
            'id_situacao' => 'required|integer|exists:situacao,id_situacao', // Garante que a situação existe na tabela 'situacao'
            'id_evento' => 'required|integer|exists:eventos,id_evento', // Garante que o evento existe na tabela 'eventos'
            // 'data_criacao' e 'data_aprovacao' são opcionais aqui se forem gerenciados pelo DB ou posteriormente
            'data_criacao' => 'nullable|date',
            'data_aprovacao' => 'nullable|date',
            'id_orientador' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
                    // Supondo que a coluna do perfil se chame 'perfil' ou 'role'
                    $query->whereIn('id_tipo_usuario', ['1','3']);
                }),
            ],
            'id_coorientador' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
                    // Supondo que a coluna do perfil se chame 'perfil' ou 'role'
                    $query->whereIn('id_tipo_usuario', ['1','3']);
                }),
            ],
        ]);

        // Se a validação falhar, retorna os erros com status HTTP 422 Unprocessable Entity.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtém todos os dados da requisição.
        $data = $request->all();

        // Cria um novo registro de projeto no banco de dados.
        $item = Projeto::create($data);

        // Retorna o projeto recém-criado com status HTTP 201 Created.
        return response()->json($item, 201);
    }
    /**
     * Exibe os detalhes de um projeto específico.
     *
     * @param  string  $id O ID do projeto a ser exibido.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o projeto encontrado ou um erro 404.
     */
    public function show(string $id)
    {
        // Tenta encontrar o projeto pelo ID.
        $item = Projeto::find($id);

        // Se o projeto for encontrado, retorna-o com status HTTP 200 OK.
        if ($item) {
            return response()->json($item, 200);
        }

        // Se o projeto não for encontrado, retorna um erro 404 Not Found.
        return response()->json(['erro' => 'Projeto não encontrado'], 404);
    }
    public function meusProjetos(string $id)
    {
        $projetos = Projeto::where('id_responsavel', $id)
            ->select('id_projeto', 'titulo', 'problema', 'id_situacao')
            ->get();

        if ($projetos->isNotEmpty()) {
            return response()->json($projetos, 200);
        }

        return response()->json(['erro' => 'Nenhum projeto encontrado para este usuário'], 404);
    }
    /**
     * Atualiza um projeto existente no banco de dados.
     *
     * @param  \Illuminate\Http\Request  $request Os dados da requisição para atualização.
     * @param  string  $id O ID do projeto a ser atualizado.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o projeto atualizado ou erros.
     */
    public function update(Request $request, string $id)
    {
        // Tenta encontrar o projeto a ser atualizado.
        $item = Projeto::find($id);

        // Se o projeto não for encontrado, retorna um erro 404 Not Found.
        if (!$item) {
            return response()->json(['erro' => 'Projeto não encontrado'], 404);
        }

        // Define as regras de validação para os dados de atualização.
        $validator = Validator::make($request->all(), [
            'id_responsavel' => 'required|integer|exists:usuarios,id_usuario', // Garante que o responsável existe na tabela 'usuarios'
            'titulo' => 'required|string|max:200',
            'problema' => 'required|string',
            'relevancia' => 'required|string',
            'id_situacao' => 'required|integer|exists:situacao,id_situacao', // Garante que a situação existe na tabela 'situacao'
            'id_evento' => 'required|integer|exists:eventos,id_evento', // Garante que o evento existe na tabela 'eventos'
            // 'data_criacao' e 'data_aprovacao' são opcionais aqui se forem gerenciados pelo DB ou posteriormente
            'data_criacao' => 'nullable|date',
            'data_aprovacao' => 'nullable|date',
            'id_orientador' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
                    // Supondo que a coluna do perfil se chame 'perfil' ou 'role'
                    $query->whereIn('id_tipo_usuario', ['3']);
                }),
            ],
            'id_coorientador' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
                    // Supondo que a coluna do perfil se chame 'perfil' ou 'role'
                    $query->whereIn('id_tipo_usuario', ['3']);
                }),
            ],
        ]);

        // Se a validação falhar, retorna os erros com status HTTP 422 Unprocessable Entity.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtém todos os dados da requisição.
        $data = $request->all();

        // Atualiza o registro do projeto no banco de dados.
        $item->update($data);

        // Retorna o projeto atualizado com status HTTP 200 OK.
        return response()->json($item, 200);
    }

    /**
     * Remove um projeto do banco de dados.
     *
     * @param  string  $id O ID do projeto a ser removido.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON indicando sucesso (204) ou erro (404).
     */
    public function destroy(string $id)
    {
        // Tenta encontrar o projeto a ser deletado.
        $item = Projeto::find($id);

        // Se o projeto for encontrado, o deleta.
        if ($item) {
            $item->delete();
            // Retorna status HTTP 204 No Content para indicar sucesso na exclusão sem retorno de corpo.
            return response()->json(null, 204);
        }

        // Se o projeto não for encontrado, retorna um erro 404 Not Found.
        return response()->json(['erro' => 'Projeto não encontrado'], 404);
    }
}
