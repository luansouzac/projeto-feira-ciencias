<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvaliadorProjeto;
use App\Models\Projeto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvaliadorProjetoController extends Controller
{
    /**
     * Lista todas as atribuições para um projeto específico.
     * Útil para ver quem já foi designado para avaliar.
     *
     * @param  \App\Models\Projeto  $projeto
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Projeto $projeto)
    {
        $atribuicoes = AvaliadorProjeto::where('id_projeto', $projeto->id_projeto)
                                       ->with('avaliador:id_usuario,nome') // Carrega o nome do avaliador
                                       ->get();

        return response()->json($atribuicoes, 200);
    }

    /**
     * Atribui um novo avaliador a um projeto.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_projeto' => 'required|integer|exists:projetos,id_projeto',
            'id_avaliador' => 'required|integer|exists:usuarios,id_usuario',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Regra de Negócio: Verifica o limite de 3 avaliadores por projeto
        $count = AvaliadorProjeto::where('id_projeto', $request->id_projeto)->count();
        if ($count >= 3) {
            return response()->json(['erro' => 'Este projeto já atingiu o limite de 3 avaliadores.'], 409); // 409 Conflict
        }

        // O firstOrCreate previne a criação de duplicados (graças ao 'unique' na migration)
        $atribuicao = AvaliadorProjeto::firstOrCreate($validator->validated());

        return response()->json($atribuicao, 201);
    }

    /**
     * Remove (desatribui) um avaliador de um projeto.
     * O ID aqui é o da tabela 'avaliador_projeto'.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $atribuicao = AvaliadorProjeto::find($id);

        if (!$atribuicao) {
            return response()->json(['erro' => 'Atribuição não encontrada.'], 404);
        }
        
        // Regra de Negócio: Impede a remoção se a avaliação já foi concluída
        if ($atribuicao->status === 'concluida') {
            return response()->json(['erro' => 'Não é possível remover um avaliador de uma avaliação já concluída.'], 403);
        }

        $atribuicao->delete();
        return response()->json(['mensagem' => 'Avaliador desassociado com sucesso.'], 200);
    }
}

