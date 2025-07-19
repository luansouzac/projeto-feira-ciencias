<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventoController extends Controller
{
    public function index()
    {
        // Retorna todos os eventos
        return response()->json(Evento::all(), 200);
    }


    public function store(Request $request)
    {
    try {
        // Define as regras de validação para os dados de atualização.
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'ativo' => 'boolean',
            'data_evento' => 'nullable|date',
            'inicio_submissao' => 'nullable|date',
            'fim_submissao' => 'nullable|date',
            'min_pessoas' => 'required|integer|min:1',
            'max_pessoas' => 'required|integer|min:1',
        ]);

        // Se a validação falhar, retorna os erros com status HTTP 422 Unprocessable Entity.
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtém todos os dados da requisição.
        $data = $request->all();

        // Cria um novo registro de evento no banco de dados.
        $item = Evento::create($data);

        // Retorna o evento recém-criado com status HTTP 201 Created.
        return response()->json($item, 201);

        } catch (\Exception $e) {
            // Retorna qualquer outro erro com status 500
            return response()->json([
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        // Tenta encontrar o evento pelo ID.
        $item = Evento::find($id);

        // Se o evento for encontrado, retorna-o com status HTTP 200 OK.
        if ($item) {
            return response()->json($item, 200);
        }

        // Se o evento não for encontrado, retorna um erro 404 Not Found.
        return response()->json(['erro' => 'Evento não encontrado'], 404);
    }


    public function update(Request $request, string $id)
    {
        try {
            // Tenta encontrar o evento a ser atualizado.
            $item = Evento::find($id);
            
            // Se o evento não for encontrado, retorna um erro 404 Not Found.
            if (!$item) {
                return response()->json(['erro' => 'Evento não encontrado'], 404);
            }
            
            // Define as regras de validação para os dados de atualização.
            $validator = Validator::make($request->all(), [
                'nome' => 'required|string|max:255',
                'ativo' => 'boolean'
            ]);
            
            // Se a validação falhar, retorna os erros com status HTTP 422 Unprocessable Entity.
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            // Obtém todos os dados da requisição.
            $data = $request->all();
            
            // Atualiza o registro do evento no banco de dados.
            $item->update($data);
            
            // Retorna o evento atualizado com status HTTP 200 OK.
            return response()->json($item, 200);
        } catch (\Exception $e) {
            // Retorna qualquer outro erro com status 500
            return response()->json([
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    public function destroy(string $id){
        // Tenta encontrar o evento a ser deletado.
        $item = Evento::find($id);

        // Se o evento for encontrado, o deleta.
        if ($item) {
            $item->delete();
            // Retorna status HTTP 204 No Content para indicar sucesso na exclusão sem retorno de corpo.
            return response()->json(null, 204);
        }

        // Se o evento não for encontrado, retorna um erro 404 Not Found.
        return response()->json(['erro' => 'Evento não encontrado'], 404);
    }
}
