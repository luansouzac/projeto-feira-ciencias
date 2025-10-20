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
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\AvaliadorProjetoController;
use App\Http\Controllers\Api\QuestionarioController;
use App\Http\Controllers\Api\PerguntaQuestionarioController;
use App\Http\Controllers\Api\VotoPopularController;

/*
|--------------------------------------------------------------------------
| Rotas da API
|--------------------------------------------------------------------------
| Organizadas por Acesso: Públicas e Autenticadas (com subgrupos de permissões)
*/

// --- 1. ROTAS PÚBLICAS (NÃO EXIGEM AUTENTICAÇÃO) ---
Route::post('register', [UserAuthController::class, 'register']);
Route::post('login', [UserAuthController::class, 'login']);
Route::post('/recuperar_senha', [PasswordResetController::class, 'generateAndSend']);

Route::prefix('public')->group(function () {
    Route::get('/eventos/{evento}/projetos', [EventoController::class, 'publicProjects']);
    Route::get('/projetos/{projeto}', [ProjetoController::class, 'publicShow']);
});


// --- 2. ROTAS AUTENTICADAS (EXIGEM LOGIN) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [UserAuthController::class, 'logout']);

    // --- ROTAS GERAIS AUTENTICADAS ---
    Route::get('/projetos/resultados-gerais', [ProjetoController::class, 'resultadosGerais']);
    Route::get('/minhas-avaliacoes', [UsuarioController::class, 'minhasAtribuicoes']);
    Route::get('/projetos/{projeto}/minha-atribuicao', [UsuarioController::class, 'minhaAtribuicaoParaProjeto']);
    Route::post('/votos_populares', [VotoPopularController::class, 'store']);

    // --- GRUPOS ESPECÍFICOS DE PERMISSÕES ---

    // -- PERMISSÕES DE USUÁRIO --
    Route::middleware('permission:crud usuario')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class)->except(['index', 'show']);
        Route::post('/usuarioslista', [UsuarioController::class, 'inserirLista']);
    });
    Route::middleware('permission:exibir usuario')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
        Route::get('/usuarios/{id}/projetos/avaliacao', [ProjetoController::class, 'projetosAvaliacao']);
    });

    // -- PERMISSÕES DE PROJETO --
    Route::middleware('permission:crud projeto')->group(function () {
        Route::post('/projetos', [ProjetoController::class, 'store']);
        Route::put('/projetos/{projeto}', [ProjetoController::class, 'update']);
        Route::delete('/projetos/{projeto}', [ProjetoController::class, 'destroy']);
        Route::patch('/projetos/{id_projeto}/situacao', [ProjetoController::class, 'updateSituacao']);
    });
    Route::middleware('permission:exibir projeto')->group(function () {
        Route::get('/projetos', [ProjetoController::class, 'index']);
        Route::get('/projetos/{id}', [ProjetoController::class, 'show']);
        Route::get('/usuarios/{id}/projetos', [ProjetoController::class, 'meusProjetos']);
        Route::get('/usuarios/{id}/projetos-inscritos', [UsuarioController::class, 'projetosInscritos']);
    });

    // -- PERMISSÕES DE EQUIPE --
    Route::middleware('permission:crud equipe')->group(function () {
        Route::apiResource('equipes', EquipeController::class);
        Route::apiResource('membro_equipes', MembroEquipeController::class);
        Route::post('/projetos/{id}/inscrever', [ProjetoController::class, 'inscrever']);
        Route::post('/projetos/desinscrever/{equipe}/{usuario}', [MembroEquipeController::class, 'retiraMembroProjeto']);
    });
    Route::middleware('permission:exibir equipe')->group(function () {
        Route::get('/membros_projeto/{id}', [MembroEquipeController::class, 'membrosProjeto']);
    });

    // -- PERMISSÕES DE TAREFAS E FEEDBACKS --
    Route::middleware('permission:crud tarefa')->group(function () {
        Route::apiResource('tarefas', TarefaController::class);
        Route::apiResource('registros_tarefas', RegistroTarefaController::class);
    });
    Route::middleware('permission:exibir tarefa')->group(function () {
        Route::get('/projetos/{id_projeto}/tarefas', [TarefaController::class, 'tarefasProjeto']);
    });
    Route::middleware('permission:crud comentario desenvolvimento')->group(function () {
        Route::get('/tarefas/{tarefa}/feedbacks', [TarefaFeedbackController::class, 'index']);
        Route::post('/tarefas/{tarefa}/feedbacks', [TarefaFeedbackController::class, 'store']);
        Route::apiResource('tarefa_feedbacks', TarefaFeedbackController::class)->except(['index', 'store']);
    });
    
    // -- PERMISSÕES DE EVENTO --
    Route::middleware('permission:crud evento')->group(function () {
        Route::apiResource('eventos', EventoController::class)->except(['index', 'show']);
    });
    Route::middleware('permission:exibir evento')->group(function () {
        Route::get('/eventos', [EventoController::class, 'index']);
        Route::get('/eventos/{id}', [EventoController::class, 'show']);
    });

    // -- PERMISSÕES DE AVALIAÇÃO --
    Route::middleware('permission:crud avaliacao projeto')->group(function () {
        // Atribuição de avaliadores
        Route::get('/projetos/{projeto}/avaliadores', [AvaliadorProjetoController::class, 'index']);
        Route::post('/avaliador_projeto', [AvaliadorProjetoController::class, 'store']);
        Route::delete('/avaliador_projeto/{id}', [AvaliadorProjetoController::class, 'destroy']);
        // Gestão de Questionários
        Route::apiResource('questionarios', QuestionarioController::class);
        Route::apiResource('perguntas_questionario', PerguntaQuestionarioController::class);
        // Submissão de Avaliação Oficial
        Route::post('/avaliacoes', [AvaliacaoAprendizagemController::class, 'store']);
    });
    Route::middleware('permission:exibir avaliacao projeto')->group(function () {
        Route::get('/projetos/{projeto}/avaliacoes', [AvaliacaoController::class, 'getByProject']);
        Route::apiResource('projeto_avaliacoes', AvaliacaoController::class);
    });

    // Outros resources (podem ser movidos para os seus próprios grupos de permissão se necessário)
    Route::apiResource('objetivo_projetos', ObjetivoProjetoController::class);
    Route::apiResource('questao_pesquisas', QuestaoPesquisaController::class);
});