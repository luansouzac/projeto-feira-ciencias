<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionarioController extends Controller
{
    /**
     * Lista todos os questionários.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Carrega os questionários com as suas perguntas para uma resposta completa
        $questionarios = Questionario::with('perguntas')->get();
        return response()->json($questionarios, 200);
    }

    /**
     * Cria um novo questionário.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_evento' => 'required|integer|exists:eventos,id_evento',
            'titulo' => 'required|string|max:255',
            'ativo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $questionario = Questionario::create($validator->validated());
        return response()->json($questionario, 201);
    }

    /**
     * Exibe um questionário específico com as suas perguntas.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $questionario = Questionario::with('perguntas')->find($id);

        if (!$questionario) {
            return response()->json(['erro' => 'Questionário não encontrado'], 404);
        }
        return response()->json($questionario, 200);
    }

    /**
     * Atualiza um questionário.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $questionario = Questionario::find($id);
        if (!$questionario) {
            return response()->json(['erro' => 'Questionário não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_evento' => 'sometimes|required|integer|exists:eventos,id_evento',
            'titulo' => 'sometimes|required|string|max:255',
            'ativo' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $questionario->update($validator->validated());
        return response()->json($questionario, 200);
    }

    /**
     * Remove um questionário.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $questionario = Questionario::find($id);
        if (!$questionario) {
            return response()->json(['erro' => 'Questionário não encontrado'], 404);
        }

        $questionario->delete();
        return response()->json(['mensagem' => 'Questionário removido com sucesso'], 200);
    }
}
