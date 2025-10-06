<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tarefa;
use App\Models\TarefaFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TarefaFeedbackController extends Controller
{
    /**
     * Lista todos os feedbacks de uma tarefa específica.
     *
     * @param  \App\Models\Tarefa  $tarefa
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Tarefa $tarefa)
    {
        $feedbacks = $tarefa->feedbacks()
                            ->with('usuario') // Carrega os dados do usuário que comentou
                            ->latest()      // Ordena pelos mais recentes
                            ->get();
                            
        return response()->json($feedbacks, 200);
    }

    /**
     * Armazena um novo feedback para uma tarefa.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tarefa  $tarefa
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Tarefa $tarefa)
{
    $dadosValidados = $request->validate([
        'feedback' => 'required|string|min:5|max:2000',
    ]);

    $tarefa->load('projeto.equipe.membroEquipe');
    $usuario = Auth::user();

    $eOrientador = $tarefa->projeto->id_orientador == $usuario->id_usuario;
    $eMembroDaEquipe = false;
    if ($tarefa->projeto->equipe->first()) {
        $eMembroDaEquipe = $tarefa->projeto->equipe->first()->membroEquipe->contains('id_usuario', $usuario->id_usuario);
    }

    if (!$eOrientador && !$eMembroDaEquipe) {
        return response()->json(['message' => 'Você não tem permissão para interagir com esta tarefa.'], 403);
    }

    $feedback = $tarefa->feedbacks()->create([
        'feedback' => $dadosValidados['feedback'],
        'id_usuario' => $usuario->id_usuario,
    ]);

    $feedback->load('usuario.tipoUsuario');

    return response()->json($feedback, 201);
}

    /**
     * Exibe um feedback específico.
     *
     * @param  \App\Models\TarefaFeedback  $feedback
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(TarefaFeedback $feedback)
    {
        // Carrega o relacionamento com o usuário para garantir que a resposta seja completa
        $feedback->load('usuario');
        return response()->json($feedback, 200);
    }

    /**
     * Atualiza um feedback existente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TarefaFeedback  $feedback
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, TarefaFeedback $feedback)
    {
        // Garante que apenas o usuário que criou o feedback possa editá-lo
        if ($feedback->id_usuario !== Auth::id()) {
            return response()->json(['erro' => 'Não autorizado'], 403);
        }

        $dadosValidados = $request->validate([
            'feedback' => 'required|string|min:5',
        ]);

        $feedback->update($dadosValidados);

        // Carrega o relacionamento com o usuário para a resposta
        $feedback->load('usuario');

        return response()->json($feedback, 200);
    }

    /**
     * Remove um feedback do banco de dados.
     *
     * @param  \App\Models\TarefaFeedback  $feedback
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(TarefaFeedback $feedback)
    {
        // Garante que apenas o usuário que criou o feedback possa excluí-lo
        if ($feedback->id_usuario !== Auth::id()) {
            return response()->json(['erro' => 'Não autorizado'], 403);
        }

        $feedback->delete();

        return response()->json(['mensagem' => 'Feedback removido com sucesso'], 200);
    }
}

