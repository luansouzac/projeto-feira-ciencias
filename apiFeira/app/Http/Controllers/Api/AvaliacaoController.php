<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projeto;
use App\Models\ProjetoAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

class AvaliacaoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(ProjetoAvaliacao::all(), 200);
    }
    public function getByProject(Projeto $projeto)
    {
        $avaliacoes = ProjetoAvaliacao::where('id_projeto', $projeto->id_projeto)->get();
        return response()->json($avaliacoes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $dadosValidados = $request->validate([
            'id_projeto' => 'required|integer|exists:projetos,id_projeto',
            'id_avaliador' => 'required|integer|exists:usuarios,id_usuario',
            'id_situacao' => 'required|integer|exists:situacao,id_situacao',
            'feedback' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $avaliacao = ProjetoAvaliacao::create($dadosValidados);

            $projeto = Projeto::find($dadosValidados['id_projeto']);
            $projeto->id_situacao = $dadosValidados['id_situacao']; 
            $projeto->save();

            DB::commit();

            return response()->json($avaliacao->load('situacao'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => 'Ocorreu um erro ao salvar a avaliação.', 'detalhes' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = ProjetoAvaliacao::find($id);
        if($item){
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'Avaliacao do projeto nao encontrado'],404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = ProjetoAvaliacao::find($id);
        if($item){
            $item->update($request->all());
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'Tarefa nao encontrado'],404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = ProjetoAvaliacao::find($id);
        if($item){
            $item->delete();
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'Tarefa nao encontrado'],404);
    }
}
