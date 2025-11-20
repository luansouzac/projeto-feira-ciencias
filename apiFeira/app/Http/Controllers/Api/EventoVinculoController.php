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
    // Define os models e as regras para Orientadores e Avaliadores
    private $models = [
        'orientadores' => ['model' => EventoOrientador::class, 'foreign_key' => 'id_orientador', 'role_ids' => [3, 4]], 
        'avaliadores' => ['model' => EventoAvaliador::class, 'foreign_key' => 'id_avaliador', 'role_ids' => [3, 4]], 
    ];

    // --- MÉTODOS DE LEITURA (GET) ---

    /**
     * Lista todos os usuários vinculados a um evento (Orientadores ou Avaliadores).
     */
    public function index(Evento $evento, string $tipo)
    {
        if (!isset($this->models[$tipo])) {
            return response()->json(['erro' => 'Tipo de vínculo inválido.'], 400);
        }

        $modelConfig = $this->models[$tipo];
        
        $query = $modelConfig['model']::where('id_evento', $evento->id_evento);
        
        // ✅ CORREÇÃO: Carrega APENAS o relacionamento que existe no modelo atual.
        if ($tipo === 'orientadores') {
             // O model EventoOrientador só tem a relação 'orientador'
            $query->with('orientador:id_usuario,nome,email');
        } elseif ($tipo === 'avaliadores') {
            // O model EventoAvaliador só tem a relação 'avaliador'
            $query->with('avaliador:id_usuario,nome,email');
        }

        $vinculos = $query->get();
        
        return response()->json($vinculos, 200);
    }

    // --- MÉTODOS DE ESCRITA (POST, DELETE) ---

    /**
     * Vincula um usuário a um evento.
     */
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
                // Validação verifica se o usuário tem o perfil 3 OU 4
                Rule::exists('usuarios', 'id_usuario')->where(function ($query) use ($modelConfig) {
                    $query->whereIn('id_tipo_usuario', $modelConfig['role_ids']); 
                })
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Verifica se o vínculo já existe
        $jaVinculado = $modelConfig['model']::where('id_evento', $evento->id_evento)
            ->where($foreignKey, $request->id_usuario)
            ->exists();
            
        if ($jaVinculado) {
            return response()->json(['erro' => 'Usuário já está vinculado a este evento.'], 409);
        }

        // Cria o novo vínculo
        $vinculo = $modelConfig['model']::create([
            'id_evento' => $evento->id_evento,
            $foreignKey => $request->id_usuario,
        ]);
        
        // ✅ CORREÇÃO: Carrega APENAS o relacionamento relevante para o feedback
        if ($tipo === 'orientadores') {
            $vinculo->load('orientador:id_usuario,nome,email');
        } elseif ($tipo === 'avaliadores') {
            $vinculo->load('avaliador:id_usuario,nome,email');
        }

        return response()->json($vinculo, 201);
    }

    /**
     * Desvincula um usuário de um evento.
     */
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