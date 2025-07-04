<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AvaliacaoAprendizagem;

class AvaliacaoAprendizagemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(AvaliacaoAprendizagem::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $item = AvaliacaoAprendizagem::create($request->all());
        return response()->json($item, 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = AvaliacaoAprendizagem::find($id);
        if($item){
            return response()->json($item,200);
        }
        return response()->json(['erro' => 'AvaliacaoAprendizagem nao encontrado'],404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = AvaliacaoAprendizagem::find($id);
        if($item){
            $item->update($request->all());
            return response()->json($item,200);
        }
        return response()->json(['erro'=> 'AvaliacaoAprendizagem nao encontrado'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = AvaliacaoAprendizagem::find($id);
        if($item){
            $item->delete();
            return response()->json($item, 200);
        }
        return response()->json(['erro'=> 'AvaliacaoAprendizagem nao encontrado'],404);
    }
}
