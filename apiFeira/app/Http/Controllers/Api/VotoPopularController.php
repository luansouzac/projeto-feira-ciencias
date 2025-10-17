<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VotoPopular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VotoPopularController extends Controller
{
    /**
     * Lista todos os votos populares, talvez agrupados por projeto.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $votos = VotoPopular::with('projeto', 'usuario')->get();
        return response()->json($votos, 200);
    }

    /**
     * Regista um novo voto popular.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_projeto' => 'required|integer|exists:projetos,id_projeto',
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // A regra 'unique' na migration já impede votos duplicados,
        // mas podemos verificar aqui para uma mensagem de erro mais amigável.
        $jaVotou = VotoPopular::where('id_projeto', $request->id_projeto)
                               ->where('id_usuario', $request->id_usuario)
                               ->exists();

        if ($jaVotou) {
            return response()->json(['erro' => 'Você já votou neste projeto.'], 409); // Conflict
        }

        $voto = VotoPopular::create($validator->validated());
        return response()->json($voto, 201);
    }

    /**
     * Exibe um voto popular específico.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $voto = VotoPopular::with('projeto', 'usuario')->find($id);
        if (!$voto) {
            return response()->json(['erro' => 'Voto não encontrado'], 404);
        }
        return response()->json($voto, 200);
    }

    /**
     * A atualização de um voto não é uma operação comum.
     */
    public function update(Request $request, string $id)
    {
         return response()->json(['erro' => 'A atualização de votos não é permitida.'], 405);
    }

    /**
     * Remove um voto popular (ação de administrador).
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $voto = VotoPopular::find($id);
        if (!$voto) {
            return response()->json(['erro' => 'Voto não encontrado'], 404);
        }
        
        $voto->delete();
        return response()->json(['mensagem' => 'Voto removido com sucesso'], 200);
    }
}
