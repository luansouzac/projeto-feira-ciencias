<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvaliacaoAprendizagem;
use App\Models\AvaliadorProjeto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AvaliacaoAprendizagemController extends Controller
{
    /**
     * Lista todas as avaliações submetidas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $avaliacoes = AvaliacaoAprendizagem::with('atribuicao.projeto', 'atribuicao.avaliador', 'respostas')->get();
        return response()->json($avaliacoes, 200);
    }

    /**
     * Armazena uma nova avaliação completa, baseada numa atribuição.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_avaliador_projeto' => 'required|integer|exists:avaliador_projeto,id',
            'nota_geral' => 'required|numeric|min:0|max:100',
            'observacoes' => 'nullable|string',
            'respostas' => 'required|array',
            'respostas.*.id_pergunta' => 'required|integer|exists:perguntas_questionario,id_pergunta',
            'respostas.*.valor_resposta' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dadosValidados = $validator->validated();
        $atribuicao = AvaliadorProjeto::find($dadosValidados['id_avaliador_projeto']);

        // --- REGRAS DE NEGÓCIO ---
        // 1. Garante que o utilizador logado é o mesmo da atribuição
        if ($atribuicao->id_avaliador !== $request->user()->id_usuario) {
            return response()->json(['erro' => 'Não autorizado. Esta avaliação não está atribuída a você.'], 403);
        }
        // 2. Garante que esta avaliação ainda não foi submetida
        if ($atribuicao->status === 'concluida') {
            return response()->json(['erro' => 'Esta avaliação já foi submetida.'], 409); // Conflict
        }

        // Inicia uma transação para garantir a integridade dos dados
        DB::beginTransaction();
        try {
            // Cria a avaliação principal
            $avaliacao = $atribuicao->avaliacao()->create([
                'nota_geral' => $dadosValidados['nota_geral'],
                'observacoes' => $dadosValidados['observacoes'],
            ]);

            // Cria as respostas individuais para cada pergunta
            foreach ($dadosValidados['respostas'] as $resposta) {
                $avaliacao->respostas()->create($resposta);
            }

            // Atualiza o status da atribuição para 'concluida'
            $atribuicao->status = 'concluida';
            $atribuicao->save();

            DB::commit(); // Confirma as operações se tudo correu bem
            
            $avaliacao->load('respostas');
            return response()->json($avaliacao, 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Desfaz as operações se ocorreu um erro
            return response()->json(['erro' => 'Falha ao registar a avaliação.', 'detalhes' => $e->getMessage()], 500);
        }
    }

    /**
     * Exibe uma avaliação específica.
     *
     * @param  string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $avaliacao = AvaliacaoAprendizagem::with('atribuicao.projeto', 'atribuicao.avaliador', 'respostas.pergunta')->find($id);
        if (!$avaliacao) {
            return response()->json(['erro' => 'Avaliação não encontrada'], 404);
        }
        return response()->json($avaliacao, 200);
    }

    /**
     * A atualização de uma avaliação submetida não é permitida.
     */
    public function update(Request $request, string $id)
    {
        return response()->json(['erro' => 'A atualização de avaliações não é permitida.'], 405); // Method Not Allowed
    }

    /**
     * Remove uma avaliação (ação de administrador).
     */
    public function destroy(string $id)
    {
        $avaliacao = AvaliacaoAprendizagem::find($id);
        if (!$avaliacao) {
            return response()->json(['erro' => 'Avaliação não encontrada'], 404);
        }
        
        $avaliacao->delete();
        return response()->json(['mensagem' => 'Avaliação removida com sucesso'], 200);
    }
}
