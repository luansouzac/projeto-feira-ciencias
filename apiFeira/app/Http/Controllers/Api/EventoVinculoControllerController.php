<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\EventoOrientador;
use App\Models\EventoAvaliador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EventoVinculoController extends Controller
{
    private $models = [
        'orientadores' => ['model' => EventoOrientador::class, 'foreign_key' => 'id_orientador', 'role_ids' => [3, 4]], // 3, 4 = Tipos de Professor/Orientador
        'avaliadores' => ['model' => EventoAvaliador::class, 'foreign_key' => 'id_avaliador', 'role_ids' => [3]], // 3 = Tipo de Avaliador
    ];

    // --- MÉTODOS DE LEITURA (GET) ---

    public function index(Evento $evento, string $tipo)
    {
        if (!isset($this->models[$tipo])) {
            return response()->json(['erro' => 'Tipo de vínculo inválido.'], 400);
        }

        $modelConfig = $this->models[$tipo];
        
        $vinculos = $modelConfig['model']::where('id_evento', $evento->id_evento)
            ->with('usuario:id_usuario,nome')
            ->get();


        return response()->json($vinculos, 200);
    }

    // --- MÉTODOS DE ESCRITA (POST, DELETE) ---

    public function store(Request $request, Evento $evento, string $tipo)
    {
        if (!isset($this->models[$tipo])) {
            return response()->json(['erro' => 'Tipo de vínculo inválido.'], 400);
        }
        
        $modelConfig = $this->models[$tipo];
        $foreignKey = $modelConfig['foreign_key'];
        
        $validator = Validator::make($request->all(), [
            'id_usuario' => [
                'required', 
                'integer', 
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) use ($modelConfig) {
                    $query->whereIn('id_tipo_usuario', $modelConfig['role_ids']); 
                })
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $jaVinculado = $modelConfig['model']::where('id_evento', $evento->id_evento)
            ->where($foreignKey, $request->id_usuario)
            ->exists();
            
        if ($jaVinculado) {
            return response()->json(['erro' => 'Usuário já está vinculado a este evento.'], 409);
        }

        $vinculo = $modelConfig['model']::create([
            'id_evento' => $evento->id_evento,
            $foreignKey => $request->id_usuario,
        ]);
        
        return response()->json($vinculo, 201);
    }

    public function destroy(Evento $evento, string $tipo, int $id_usuario)
    {
        if (!isset($this->models[$tipo])) {
            return response()->json(['erro' => 'Tipo de vínculo inválido.'], 400);
        }
        
        $modelConfig = $this->models[$tipo];
        $foreignKey = $modelConfig['foreign_key'];
        
        $vinculo = $modelConfig['model']::where('id_evento', $evento->id_evento)
            ->where($foreignKey, $id_usuario)
            ->first();

        if (!$vinculo) {
            return response()->json(['erro' => 'Vínculo não encontrado.'], 404);
        }
        
        $vinculo->delete();

        return response()->json(['mensagem' => 'Vínculo removido com sucesso.'], 200);
    }
}