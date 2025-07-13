<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tarefa;
use App\Models\Projeto;

class TarefaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Tarefa::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $item = Tarefa::create($request->all());
        return response()->json($item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = Tarefa::find($id);
        if($item){
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'Tarefa nao encontrado'],404);
    }
    public function tarefasProjeto(string $id_projeto)
    {
    
        if (!Projeto::find($id_projeto)) {
            return response()->json(['erro' => 'Projeto não encontrado'], 404);
        }
        
        $tarefas = Tarefa::where('id_projeto', $id_projeto)->get();

        return response()->json($tarefas, 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = Tarefa::find($id);
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
        $item = Tarefa::find($id);
        if($item){
            $item->delete();
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'Tarefa nao encontrado'],404);
    }
}
