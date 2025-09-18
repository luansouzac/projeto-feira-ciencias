<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projeto;
use App\Models\Equipe;
use App\Models\MembroEquipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProjetoController extends Controller
{
    /**
     * Exibe uma lista de todos os projetos.
     *
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com todos os projetos.
     */
    public function index(Request $request)
    {
      
        $query = Projeto::with('responsavel', 'orientador', 'coorientador', 'situacao', 'eventos', 'equipe.membroEquipe');

        if ($request->filled('id_responsavel')) {
            $query->where('id_responsavel', $request->input('id_responsavel'));
        }

        if ($request->filled('id_situacao')) {
            $query->where('id_situacao', $request->input('id_situacao'));
        }

        if ($request->filled('situacao_in')) {
            $listaDeStatus = explode(',', $request->input('situacao_in'));
            $query->whereIn('id_situacao', $listaDeStatus);
        }
        if ($request->filled('situacao_not')) {
            $query->where('id_situacao', '!=', $request->input('situacao_not'));
        }

        $projetos = $query->get();
        
        return response()->json($projetos, 200);
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
        $validator = Validator::make($request->all(), [
            'id_responsavel' => 'required|integer|exists:usuarios,id_usuario',
            'titulo' => 'required|string|max:200',
            'problema' => 'required|string',
            'relevancia' => 'required|string',
            'id_situacao' => 'required|integer|exists:situacao,id_situacao',
            'id_evento' => 'required|integer|exists:eventos,id_evento',
            'data_criacao' => 'nullable|date',
            'data_aprovacao' => 'nullable|date',
            'id_orientador' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
                    $query->whereIn('id_tipo_usuario', ['1','4']);
                }),
            ],
            // 'id_coorientador' => [
            //     'required',
            //     'integer',
            //     Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
            //         $query->whereIn('id_tipo_usuario', ['1','3']);
            //     }),
            // ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $item = Projeto::create($data);

        return response()->json($item, 201);
    }

    /**
     * Inscreve o usuário autenticado em um projeto.
     * A função foi refeita para se alinhar aos modelos fornecidos.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id O ID do projeto.
     * @return \Illuminate\Http\JsonResponse
     */
    public function inscrever(Request $request, $id)
    {
        // Usamos findOrFail para garantir que o projeto exista, senão retorna 404.
        // Carregamos o evento relacionado para verificar o número máximo de pessoas.
        $projeto = Projeto::with('eventos')->findOrFail($id);
        
        $usuario = $request->user();

        // 1. Encontra a equipe do projeto ou cria uma nova se não existir.
        $equipe = Equipe::firstOrCreate(['id_projeto' => $projeto->id_projeto]);

        // 2. Verifica se o usuário já é membro desta equipe.
        $jaInscrito = MembroEquipe::where('id_equipe', $equipe->id_equipe)
                                  ->where('id_usuario', $usuario->id_usuario)
                                  ->exists();

        if ($jaInscrito) {
            return response()->json(['erro' => 'Você já está inscrito neste projeto.'], 409); // 409 Conflict
        }

        // 3. Verifica se o projeto atingiu o limite de membros definido no evento.
        $limiteMembros = $projeto->evento->max_pessoas ?? 0;
        $membrosAtuais = $equipe->membroEquipe()->count();

        if ($limiteMembros > 0 && $membrosAtuais >= $limiteMembros) {
            return response()->json(['erro' => 'Este projeto já atingiu o número máximo de participantes.'], 403); // 403 Forbidden
        }

        // 4. Cria o registro do novo membro na equipe.
        // O modelo MembroEquipe espera 'id_funcao'. Assumimos um valor padrão para "Membro".
        // Você pode precisar criar uma tabela 'funcoes' e definir um ID padrão.
        $membro = MembroEquipe::create([
            'id_equipe' => $equipe->id_equipe,
            'id_usuario' => $usuario->id_usuario,
            'id_funcao' => 2, // Assumindo que '2' é o ID para a função de 'Membro'
        ]);

        return response()->json([
            'message' => 'Inscrição realizada com sucesso!',
            'membro' => $membro
        ], 201); // 201 Created
    }

    /**
     * Exibe os detalhes de um projeto específico.
     *
     * @param  string  $id O ID do projeto a ser exibido.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o projeto encontrado ou um erro 404.
     */
    public function show(string $id)
    {
        $item = Projeto ::with(['responsavel', 'orientador', 'coorientador'])->find($id);

        if ($item) {
            return response()->json($item, 200);
        }

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
    public function projetosAvaliacao(string $id)
    {
        $projetos = Projeto::with(['responsavel', 'eventos', 'orientador', 'coorientador'])
            ->where('id_orientador', $id)
            ->get();

        return response()->json($projetos, 200);
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
        $item = Projeto::find($id);

        if (!$item) {
            return response()->json(['erro' => 'Projeto não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_responsavel' => 'required|integer|exists:usuarios,id_usuario',
            'titulo' => 'required|string|max:200',
            'problema' => 'required|string',
            'relevancia' => 'required|string',
            'id_situacao' => 'required|integer|exists:situacao,id_situacao',
            'id_evento' => 'required|integer|exists:eventos,id_evento',
            'data_criacao' => 'nullable|date',
            'data_aprovacao' => 'nullable|date',
            'id_orientador' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
                    $query->whereIn('id_tipo_usuario', ['3']);
                }),
            ],
            // 'id_coorientador' => [
            //     'required',
            //     'integer',
            //     Rule::exists('usuarios', 'id_usuario')->where(function ($query) {
            //         $query->whereIn('id_tipo_usuario', ['3']);
            //     }),
            // ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $item->update($data);

        return response()->json($item, 200);
    }
    public function updateSituacao(Request $request, $id)
    {
        $projeto = Projeto::findOrFail($id);

        $validatedData = $request->validate([
            'id_situacao' => 'required|integer',
        ]);

        $projeto->update($validatedData);

        return response()->json($projeto);
    }

    /**
     * Remove um projeto do banco de dados.
     *
     * @param  string  $id O ID do projeto a ser removido.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON indicando sucesso (204) ou erro (404).
     */
    public function destroy(string $id)
    {
        $item = Projeto::find($id);

        if ($item) {
            $item->delete();
            return response()->json(null, 204);
        }
        
        return response()->json(['erro' => 'Projeto não encontrado'], 404);
    }
}

