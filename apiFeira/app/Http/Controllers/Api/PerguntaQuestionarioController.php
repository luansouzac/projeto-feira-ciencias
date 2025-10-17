<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerguntaQuestionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PerguntaQuestionarioController extends Controller
{
    /**
     * Lista todas as perguntas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(PerguntaQuestionario::all(), 200);
    }

    /**
     * Cria uma nova pergunta para um questionário.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_questionario' => 'required|integer|exists:questionarios,id_questionario',
            'criterio' => 'required|string|max:100',
            'texto_pergunta' => 'required|string',
            'ordem' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pergunta = PerguntaQuestionario::create($validator->validated());
        return response()->json($pergunta, 201);
    }

    /**
     * Exibe uma pergunta específica.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $pergunta = PerguntaQuestionario::find($id);
        if (!$pergunta) {
            return response()->json(['erro' => 'Pergunta não encontrada'], 404);
        }
        return response()->json($pergunta, 200);
    }

    /**
     * Atualiza uma pergunta.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $pergunta = PerguntaQuestionario::find($id);
        if (!$pergunta) {
            return response()->json(['erro' => 'Pergunta não encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_questionario' => 'sometimes|required|integer|exists:questionarios,id_questionario',
            'criterio' => 'sometimes|required|string|max:100',
            'texto_pergunta' => 'sometimes|required|string',
            'ordem' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pergunta->update($validator->validated());
        return response()->json($pergunta, 200);
    }

    /**
     * Remove uma pergunta.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $pergunta = PerguntaQuestionario::find($id);
        if (!$pergunta) {
            return response()->json(['erro' => 'Pergunta não encontrada'], 404);
        }
        
        $pergunta->delete();
        return response()->json(['mensagem' => 'Pergunta removida com sucesso'], 200);
    }
}
