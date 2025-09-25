<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equipe;

class EquipeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Equipe::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $item = Equipe::create($request->all());
        return response()->json($item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = Equipe::find($id);
        if($item){
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'Equipe nao encontrada'], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = Equipe::find($id);
        if($item){
            $item->update($request->all());
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'Equipe nao encontrada'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = Equipe::find($id);
        if($item){
            $item->delete();
            return response()->json(['message' => 'Equipe deletada com sucesso'], 200);
        }
        return response()->json(['erro' => 'Equipe nao encontrada'], 404);
    }

    public function destroyProjeto(string $id_projeto)
    {
        $item = Equipe::where('id_projeto', $id_projeto)->first();
        if($item){
            $item->delete();
            return response()->json(['message' => 'Equipe deletada com sucesso'], 200);
        }
        return response()->json(['erro' => 'Equipe nao encontrada'], 404);
    }
}
