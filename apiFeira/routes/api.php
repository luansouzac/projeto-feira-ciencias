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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('projetos', ProjetoController::class);
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
//Route::apiResource('equipes', EquipeController::class); //colocar o nome da tabela e o nome do controller

Route::post('register', [UserAuthController::class, 'register']);
Route::post('login', [UserAuthController::class, 'login']);
Route::post('logout', [UserAuthController::class, 'logout'])->middleware('auth:sanctum');
