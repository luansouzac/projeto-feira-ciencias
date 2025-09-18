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
use App\Http\Controllers\Api\EquipeController;
use App\Http\Controllers\Api\TarefaFeedbackController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Exibir Usuário e Crud Usuário
Route::middleware(['auth:sanctum', 'permission:crud usuario'])->group(function () {
    Route::apiResource('usuarios', UsuarioController::class);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
    Route::get('/usuarios/{id}/projetos', [ProjetoController::class, 'meusProjetos']);
    Route::get('/usuarios/{id}/projetos/avaliacao', [ProjetoController::class, 'projetosAvaliacao']);
});

Route::middleware(['auth:sanctum', 'permission:exibir usuario'])->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
    Route::get('/usuarios/{id}/projetos', [ProjetoController::class, 'meusProjetos']);
    Route::get('/usuarios/{id}/projetos/avaliacao', [ProjetoController::class, 'projetosAvaliacao']);
});

// Rotas de LEITURA (acessíveis por quem pode 'exibir projeto')
Route::middleware(['auth:sanctum', 'permission:exibir projeto'])->group(function () {
    Route::get('/projetos', [ProjetoController::class, 'index']);
    Route::get('/projetos/{id}', [ProjetoController::class, 'show']);
    Route::get('/usuarios/{id}/projetos', [ProjetoController::class, 'meusProjetos']);
});

// Rotas de ESCRITA (acessíveis por quem pode 'crud projeto')
Route::middleware(['auth:sanctum', 'permission:crud projeto'])->group(function () {
    Route::post('/projetos', [ProjetoController::class, 'store']);
    Route::put('/projetos/{projeto}', [ProjetoController::class, 'update']);
    Route::patch('/projetos/{projeto}', [ProjetoController::class, 'update']);
    Route::delete('/projetos/{projeto}', [ProjetoController::class, 'destroy']);
    Route::patch('/projetos/{id_projeto}/situacao', [ProjetoController::class, 'updateSituacao']);
});

//Exibir Equipe e Crud Equipe
Route::middleware(['auth:sanctum', 'permission:crud equipe'])->group(function () {
    Route::get('/membros_projeto/{id}', [MembroEquipeController::class, 'membrosProjeto']);
    Route::apiResource('membro_equipes', MembroEquipeController::class);
    Route::apiResource('equipes', EquipeController::class);
    Route::post('/projetos/{id}/inscrever', [ProjetoController::class, 'inscrever']);
    Route::post('/projetos/desinscrever/{equipe}/{usuario}', [MembroEquipeController::class, 'retiraMembroProjeto']);
});

Route::middleware(['auth:sanctum', 'permission:exibir equipe'])->group(function () {
    Route::get('/membro_equipes', [MembroEquipeController::class, 'index']);
    Route::get('/membro_equipes/{id}', [MembroEquipeController::class, 'show']);
    Route::get('/membros_projeto/{id}', [MembroEquipeController::class, 'membrosProjeto']);
});

//Exibir Objetivo e Crud Objetivo
Route::middleware(['auth:sanctum', 'permission:crud objetivo'])->group(function () {
    Route::apiResource('questao_pesquisas', QuestaoPesquisaController::class);
    Route::apiResource('objetivo_projetos', ObjetivoProjetoController::class);
});

Route::middleware(['auth:sanctum', 'permission:exibir objetivo'])->group(function () {
    Route::get('/questao_pesquisas', [QuestaoPesquisaController::class, 'index']);
    Route::get('/questao_pesquisas/{id}', [QuestaoPesquisaController::class, 'show']);
    Route::get('/objetivo_projetos', [ObjetivoProjetoController::class, 'index']);
    Route::get('/objetivo_projetos/{id}', [ObjetivoProjetoController::class, 'show']);
});

//Exibir Tarefas e Crud Tarefas
Route::middleware(['auth:sanctum', 'permission:crud tarefa'])->group(function () {
    Route::apiResource('tarefas', TarefaController::class);
    Route::apiResource('atribuicao_tarefas', AtribuicaoTarefaController::class);
    Route::get('/projetos/{id_projeto}/tarefas', [TarefaController::class, 'tarefasProjeto']);
    Route::apiResource('registros_tarefas', RegistroTarefaController::class);
    Route::get('/projetos/{id_projeto}/tarefas', [TarefaController::class, 'tarefasProjeto']);
});

Route::middleware(['auth:sanctum', 'permission:exibir tarefa'])->group(function () {
    Route::get('/tarefas', [TarefaController::class, 'index']);
    Route::get('/tarefas/{id}', [TarefaController::class, 'show']);
    Route::get('/atribuicao_tarefas', [AtribuicaoTarefaController::class, 'index']);
    Route::get('/atribuicao_tarefas/{id}', [AtribuicaoTarefaController::class, 'show']);
    Route::get('/projetos/{id_projeto}/tarefas', [TarefaController::class, 'tarefasProjeto']);
    Route::get('/registros_tarefas', [RegistroTarefaController::class, 'index']);
    Route::get('/registros_tarefas/{id}', [RegistroTarefaController::class, 'show']);
});

//Exibir Apresentação e Crud Apresentação
Route::middleware(['auth:sanctum', 'permission:crud apresentacao'])->group(function () {
    Route::apiResource('apresentacao_projetos', ApresentacaoProjetoController::class);
});

Route::middleware(['auth:sanctum', 'permission:exibir apresentacao'])->group(function () {
    Route::get('/apresentacao_projetos', [ApresentacaoProjetoController::class, 'index']);
    Route::get('/apresentacao_projetos/{id}', [ApresentacaoProjetoController::class, 'show']);
});

//Exibir Evento e Crud Evento
Route::middleware(['auth:sanctum', 'permission:crud evento'])->group(function () {
    Route::apiResource('eventos', EventoController::class);
});

Route::middleware(['auth:sanctum', 'permission:exibir evento'])->group(function () {
    Route::get('/eventos', [EventoController::class, 'index']);
    Route::get('/eventos/{id}', [EventoController::class, 'show']);
});

//Exibir Avaliação e Crud Avaliação
Route::middleware(['auth:sanctum', 'permission:crud avaliacao'])->group(function () {
    Route::apiResource('avaliacao_aprendizagem', AvaliacaoAprendizagemController::class);
});

Route::middleware(['auth:sanctum', 'permission:exibir avaliacao'])->group(function () {
    Route::get('/avaliacao_aprendizagem', [AvaliacaoAprendizagemController::class, 'index']);
    Route::get('/avaliacao_aprendizagem/{id}', [AvaliacaoAprendizagemController::class, 'show']);
});

//Exibir Comentários Planejamento e Crud Comentários Planejamento
Route::middleware(['auth:sanctum', 'permission:crud comentario planejamento'])->group(function () {
    Route::apiResource('comentarios_planejamentos', ComentarioPlanejamentoController::class);
});

Route::middleware(['auth:sanctum', 'permission:exibir comentario planejamento'])->group(function () {
    Route::get('/comentarios_planejamentos', [ComentarioPlanejamentoController::class, 'index']);
    Route::get('/comentarios_planejamentos/{id}', [ComentarioPlanejamentoController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud comentario desenvolvimento'])->group(function () {
    Route::get('/tarefas/{tarefa}/feedbacks', [TarefaFeedbackController::class, 'index']);
    Route::post('/tarefas/{tarefa}/feedbacks', [TarefaFeedbackController::class, 'store']);
    Route::get('/tarefa_feedbacks/{feedback}', [TarefaFeedbackController::class, 'show']);
    Route::put('/tarefa_feedbacks/{feedback}', [TarefaFeedbackController::class, 'update']);
    Route::delete('/tarefa_feedbacks/{feedback}', [TarefaFeedbackController::class, 'destroy']);
});


Route::middleware(['auth:sanctum', 'permission:exibir feedback tarefas'])->group(function () {

});
    Route::get('/tarefas/{tarefa}/feedbacks', [TarefaFeedbackController::class, 'index']);
    Route::get('/tarefa_feedbacks/{feedback}', [TarefaFeedbackController::class, 'show']);
//Exibir Comentários avaliacao projeto e Crud avaliacao projeto
Route::middleware(['auth:sanctum', 'permission:crud avaliacao projeto'])->group(function () {
    Route::apiResource('projeto_avaliacoes', AvaliacaoController::class);
    Route::get('/projetos/{projeto}/avaliacoes', [AvaliacaoController::class, 'getByProject']);
});

Route::middleware(['auth:sanctum', 'permission:exibir avaliacao projeto'])->group(function () {
    Route::get('/projeto_avaliacoes', [AvaliacaoController::class, 'index']);
    Route::get('/projeto_avaliacoes/{id}', [AvaliacaoController::class, 'show']);
    Route::get('/projetos/{projeto}/avaliacoes', [AvaliacaoController::class, 'getByProject']);
});


//Exibir Comentários Desenvolvimento e Crud Comentários Desenvolvimento
Route::middleware(['auth:sanctum', 'permission:crud comentario desenvolvimento'])->group(function () {
    Route::apiResource('comentarios_desenvolvimentos', ComentarioDesenvolvimentoController::class);
});

Route::middleware(['auth:sanctum', 'permission:exibir comentario desenvolvimento'])->group(function () {
    Route::get('/comentarios_desenvolvimentos', [ComentarioDesenvolvimentoController::class, 'index']);
    Route::get('/comentarios_desenvolvimentos/{id}', [ComentarioDesenvolvimentoController::class, 'show']);
});

//Exibir Discussão Equipe e Crud Discussão Equipe
Route::middleware(['auth:sanctum', 'permission:crud discussao equipe'])->group(function () {
    Route::apiResource('discussao_equipes', DiscussaoEquipeController::class);
});

Route::middleware(['auth:sanctum', 'permission:exibir discussao equipe'])->group(function () {
    Route::get('/discussao_equipes', [DiscussaoEquipeController::class, 'index']);
    Route::get('/discussao_equipes/{id}', [DiscussaoEquipeController::class, 'show']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios/{id}', [UserAuthController::class, 'show']);  // Pega dados do usuário
    Route::put('/usuarios/{id}', [UserAuthController::class, 'update']); // Atualiza perfil
    Route::post('/logout', [UserAuthController::class, 'logout']);        // Logout
});

Route::post('register', [UserAuthController::class, 'register']);
Route::post('login', [UserAuthController::class, 'login']);
