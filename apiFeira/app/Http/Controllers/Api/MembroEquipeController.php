<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembroEquipe;
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
}
