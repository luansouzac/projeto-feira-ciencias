<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\ProjetoController;
use App\Http\Controllers\Api\MembroEquipeController;
use App\Http\Controllers\Api\QuestaoPesquisaController;
use App\Http\Controllers\Api\ObjetivoProjetoController;
use App\Http\Controllers\Api\TarefaController;   

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

//Route::apiResource('equipes', EquipeController::class); //colocar o nome da tabela e o nome do controller