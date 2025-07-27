<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\ProjetoController;
use App\Http\Controllers\Api\MembroEquipeController;
use App\Http\Controllers\Api\QuestaoPesquisaController;
use App\Http\Controllers\Api\ObjetivoProjetoController;
use App\Http\Controllers\Api\TarefaController;   
use App\Http\Controllers\Api\AtribuicaoTarefaController;
use App\Http\Controllers\Api\ComentarioPlanejamentoController;
use App\Http\Controllers\Api\RegistroTarefaController;
use App\Http\Controllers\Api\ComentarioDesenvolvimentoController;
use App\Http\Controllers\Api\ApresentacaoProjetoController;
use App\Http\Controllers\Api\DiscussaoEquipeController;
use App\Http\Controllers\Api\AvaliacaoAprendizagemController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\AvaliacaoController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('usuarios', UsuarioController::class);
    Route::get('/usuarios/{id}/projetos', [ProjetoController::class, 'meusProjetos']);
    Route::get('/usuarios/{id}/projetos/avaliacao', [ProjetoController::class, 'projetosAvaliacao']);
    Route::patch('projetos/{id_projeto}/situacao', [ProjetoController::class, 'updateSituacao']);
    Route::apiResource('projetos', ProjetoController::class);
    Route::get('/projetos/{id_projeto}/tarefas', [TarefaController::class, 'tarefasProjeto']);
    Route::apiResource('projeto_avaliacoes', AvaliacaoController::class);
    Route::get('/projetos/{projeto}/avaliacoes', [AvaliacaoController::class, 'getByProject']);
    Route::apiResource('membro_equipes', MembroEquipeController::class);
    Route::apiResource('questao_pesquisas', QuestaoPesquisaController::class);
    Route::apiResource('objetivo_projetos', ObjetivoProjetoController::class);
    Route::apiResource('tarefas', TarefaController::class);
    Route::apiResource('atribuicao_tarefas', AtribuicaoTarefaController::class);
    Route::apiResource('comentarios_planejamentos', ComentarioPlanejamentoController::class);
    Route::apiResource('registros_tarefas', RegistroTarefaController::class);
    Route::apiResource('comentarios_desenvolvimento', ComentarioDesenvolvimentoController::class);
    Route::apiResource('apresentacao_projetos', ApresentacaoProjetoController::class);
    Route::apiResource('discussao_equipes', DiscussaoEquipeController::class);
    Route::apiResource('avaliacao_aprendizagem', AvaliacaoAprendizagemController::class);
    Route::apiResource('eventos', EventoController::class);
    //Route::apiResource('equipes', EquipeController::class); //colocar o nome da tabela e o nome do controller
    Route::post('logout', [UserAuthController::class, 'logout']);
});

Route::post('register', [UserAuthController::class, 'register']);
Route::post('login', [UserAuthController::class, 'login']);
