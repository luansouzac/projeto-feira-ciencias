<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projeto;
use App\Models\Equipe;
use App\Models\MembroEquipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
    // Em app/Http/Controllers/ProjetoController.php
public function inscrever(Request $request, $id)
{
    $projeto = Projeto::with('eventos')->findOrFail($id);
    $usuarioIdParaAdicionar = $request->input('id_usuario', Auth::id());
    $agora = Carbon::now();

    $inicioInscricao = Carbon::parse($projeto->eventos->nicio_inscricao)->startOfDay();
    $fimInscricao = Carbon::parse($projeto->eventos->fim_inscricao)->endOfDay();

    if (!$projeto->eventos || !$agora->between($inicioInscricao, $fimInscricao)) {
        return response()->json(['message' => 'As inscrições para este projeto não estão abertas no momento.'], 403);
    }

    
    $equipe = Equipe::firstOrCreate(['id_projeto' => $projeto->id_projeto]);

    $membroEmOutraEquipe = MembroEquipe::where('id_usuario', $usuarioIdParaAdicionar)
                                       ->whereHas('equipe.projeto.eventos', function ($query) use ($projeto) {
                                           $query->where('id_evento', $projeto->id_evento); 
                                       })
                                       ->where('id_equipe', '!=', $equipe->id_equipe)
                                       ->exists();

    if ($membroEmOutraEquipe) {
        return response()->json(['message' => 'Este usuário já faz parte de outra equipe neste evento.'], 409);
    }

    $jaInscrito = $equipe->membroEquipe()->where('id_usuario', $usuarioIdParaAdicionar)->exists();
    if ($jaInscrito) {
        return response()->json(['message' => 'Este usuário já faz parte desta equipe.'], 409);
    }

    $limiteMembros = $projeto->eventos->max_pessoas ?? 0;
    if ($limiteMembros > 0 && $equipe->membroEquipe()->count() >= $limiteMembros) {
        return response()->json(['message' => 'Esta equipe já atingiu o número máximo de participantes.'], 403);
    }

    $novoMembro = MembroEquipe::create([
        'id_equipe' => $equipe->id_equipe,
        'id_usuario' => $usuarioIdParaAdicionar,
        'id_funcao' => 2,
    ]);

    $novoMembro->load('usuario.tipoUsuario');

    return response()->json($novoMembro, 201);
}

    /**
     * Exibe os detalhes de um projeto específico.
     *
     * @param  string  $id O ID do projeto a ser exibido.
     * @return \Illuminate\Http\JsonResponse Retorna uma resposta JSON com o projeto encontrado ou um erro 404.
     */
    public function show(string $id)
    {
        $item = Projeto ::with(['responsavel', 'orientador', 'coorientador', 'equipe.membroEquipe.usuario', 'equipe.membroEquipe.funcao'])->find($id);

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
                    $query->whereIn('id_tipo_usuario', ['4']);
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

