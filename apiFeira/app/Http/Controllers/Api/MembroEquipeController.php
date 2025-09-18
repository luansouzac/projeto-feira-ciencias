<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipe;
use App\Models\MembroEquipe;
use App\Models\Projeto;
use Illuminate\Http\Request;

class MembroEquipeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(MembroEquipe::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $item = MembroEquipe::create($request->all());
        return response()->json($item, 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = MembroEquipe::find($id);
        if($item){
            return response()->json($item,200);
        }
        return response()->json(['erro' => 'Membro de equipe nao encontrado'],404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = MembroEquipe::find($id);
        if($item){
            $item->update($request->all());
            return response()->json($item,200);
        }
        return response()->json(['erro'=> 'Membro equipe nao encontrado'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = MembroEquipe::find($id);
        if($item){
            $item->delete();
            return response()->json($item, 200);
        }
        return response()->json(['erro'=> 'Membro equipe nao encontrado'],404);
    }

    public function membrosProjeto(string $id_projeto)
    {
        $equipe = Equipe::where('id_projeto', $id_projeto)->first();
        if (!$equipe) {
            return response()->json(['erro' => 'Equipe não encontrada'], 404);
        }
        
        $membros = MembroEquipe::with('usuario')->where('id_equipe', $equipe->id_equipe)->get();

        return response()->json($membros, 200);
    }
    
    public function retiraMembroProjeto(string $id_projeto, string $id_usuario)
    {
        $equipe = Equipe::where('id_projeto', $id_projeto)->first();
        if (!$equipe) {
            return response()->json(['erro' => 'Equipe não encontrada'], 404);
        }
        
        $membro = MembroEquipe::with('usuario')->where('id_equipe', $equipe->id_equipe)->where('id_usuario', $id_usuario)->get();
        if($membro){
            $membro[0]->delete();
            return response()->json($membro, 200);
        }
        return response()->json(['erro'=> 'Não foi possível remover o usuário do projeto'],404);
    }
}
