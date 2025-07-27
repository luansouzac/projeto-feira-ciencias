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
use App\Models\MembroEquipe;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Exibir Usuário e Crud Usuário
Route::middleware(['auth:sanctum', 'permission:exibir usuario'])->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
});


Route::middleware(['auth:sanctum', 'permission:crud usuario'])->group(function () {
    Route::apiResource('usuarios', UsuarioController::class);
});

//Exibir Projeto e Crud Projeto
Route::middleware(['auth:sanctum', 'permission:exibir projeto'])->group(function () {
    Route::get('/projetos', [ProjetoController::class, 'index']);
    Route::get('/projetos/{id}', [ProjetoController::class, 'show']);
    Route::get('/usuarios/{id}/projetos', [ProjetoController::class, 'meusProjetos']);
});

Route::middleware(['auth:sanctum', 'permission:crud projetos'])->group(function () {
    Route::apiResource('projetos', ProjetoController::class);
});

//Exibir Equipe e Crud Equipe
Route::middleware(['auth:sanctum', 'permission:exibir equipe'])->group(function () {
    Route::get('/membro_equipes', [MembroEquipeController::class, 'index']);
    Route::get('/membro_equipes/{id}', [MembroEquipeController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud equipe'])->group(function () {
    Route::apiResource('membro_equipes', MembroEquipeController::class);
});

//Exibir Objetivo e Crud Objetivo
Route::middleware(['auth:sanctum', 'permission:exibir objetivo'])->group(function () {
    Route::get('/questao_pesquisas', [QuestaoPesquisaController::class, 'index']);
    Route::get('/questao_pesquisas/{id}', [QuestaoPesquisaController::class, 'show']);
    Route::get('/objetivo_projetos', [ObjetivoProjetoController::class, 'index']);
    Route::get('/objetivo_projetos/{id}', [ObjetivoProjetoController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud objetivo'])->group(function () {
    Route::apiResource('questao_pesquisas', QuestaoPesquisaController::class);
    Route::apiResource('objetivo_projetos', ObjetivoProjetoController::class);
});

//Exibir Tarefas e Crud Tarefas
Route::middleware(['auth:sanctum', 'permission:exibir tarefa'])->group(function () {
    Route::get('/tarefas', [TarefaController::class, 'index']);
    Route::get('/tarefas/{id}', [TarefaController::class, 'show']);
    Route::get('/atribuicao_tarefas', [AtribuicaoTarefaController::class, 'index']);
    Route::get('/atribuicao_tarefas/{id}', [AtribuicaoTarefaController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud tarefa'])->group(function () {
    Route::apiResource('tarefas', TarefaController::class);
    Route::apiResource('atribuicao_tarefas', AtribuicaoTarefaController::class);
});

//Exibir Apresentação e Crud Apresentação
Route::middleware(['auth:sanctum', 'permission:exibir apresentacao'])->group(function () {
    Route::get('/apresentacao_projetos', [ApresentacaoProjetoController::class, 'index']);
    Route::get('/apresentacao_projetos/{id}', [ApresentacaoProjetoController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud apresentacao'])->group(function () {
    Route::apiResource('apresentacao_projetos', ApresentacaoProjetoController::class);
});

//Exibir Evento e Crud Evento
Route::middleware(['auth:sanctum', 'permission:exibir evento'])->group(function () {
    Route::get('/eventos', [EventoController::class, 'index']);
    Route::get('/eventos/{id}', [EventoController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud evento'])->group(function () {
    Route::apiResource('eventos', EventoController::class);
});

//Exibir Avaliação e Crud Avaliação
Route::middleware(['auth:sanctum', 'permission:exibir avaliacao'])->group(function () {
    Route::get('/avaliacao_aprendizagem', [AvaliacaoAprendizagemController::class, 'index']);
    Route::get('/avaliacao_aprendizagem/{id}', [AvaliacaoAprendizagemController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud avaliacao'])->group(function () {
    Route::apiResource('avaliacao_aprendizagem', AvaliacaoAprendizagemController::class);
});

//Exibir Comentários Planejamento e Crud Comentários Planejamento
Route::middleware(['auth:sanctum', 'permission:exibir comentario planejamento'])->group(function () {
    Route::get('/comentarios_planejamentos', [ComentarioPlanejamentoController::class, 'index']);
    Route::get('/comentarios_planejamentos/{id}', [ComentarioPlanejamentoController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud comentario planejamento'])->group(function () {
    Route::apiResource('comentarios_planejamentos', ComentarioPlanejamentoController::class);
});

//Exibir Comentários Desenvolvimento e Crud Comentários Desenvolvimento
Route::middleware(['auth:sanctum', 'permission:exibir comentario desenvolvimento'])->group(function () {
    Route::get('/comentarios_desenvolvimentos', [ComentarioDesenvolvimentoController::class, 'index']);
    Route::get('/comentarios_desenvolvimentos/{id}', [ComentarioDesenvolvimentoController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud comentario desenvolvimento'])->group(function () {
    Route::apiResource('comentarios_desenvolvimentos', ComentarioDesenvolvimentoController::class);
});

//Exibir Discussão Equipe e Crud Discussão Equipe
Route::middleware(['auth:sanctum', 'permission:exibir discussao equipe'])->group(function () {
    Route::get('/discussao_equipes', [DiscussaoEquipeController::class, 'index']);
    Route::get('/discussao_equipes/{id}', [DiscussaoEquipeController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:crud discussao equipe'])->group(function () {
    Route::apiResource('discussao_equipes', DiscussaoEquipeController::class);
});


Route::middleware('auth:sanctum')->group(function () {
    //Route::apiResource('equipes', EquipeController::class); //colocar o nome da tabela e o nome do controller
    Route::post('logout', [UserAuthController::class, 'logout']);
});

Route::post('register', [UserAuthController::class, 'register']);
Route::post('login', [UserAuthController::class, 'login']);
