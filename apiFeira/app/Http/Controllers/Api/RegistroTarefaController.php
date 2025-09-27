<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistroTarefa;
use Illuminate\Support\Facades\Storage;

class RegistroTarefaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = RegistroTarefa::with('responsavel');

        if (request()->has('id_tarefa')) {
            $query->where('id_tarefa', request()->input('id_tarefa'));
        }

        return response()->json($query->get(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. VALIDAÇÃO DOS DADOS
        // Adicionamos regras para validar todos os campos, incluindo o arquivo.
        $validatedData = $request->validate([
            'id_tarefa' => 'required|integer|exists:tarefas,id_tarefa',
            'id_responsavel' => 'required|integer|exists:usuarios,id_usuario',
            'resultado' => 'nullable|string', // Comentário do aluno
            'data_execucao' => 'required|date',
            'arquivo' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,ppt,pptx,txt|max:5120',
        ]);

        // 2. LÓGICA PARA SALVAR O ARQUIVO (SE EXISTIR)
        if ($request->hasFile('arquivo')) {
            // Salva o arquivo na pasta 'storage/app/public/arquivos/registros_tarefas'
            // e retorna o caminho para ser salvo no banco.
            $path = $request->file('arquivo')->store('arquivos/registros_tarefas', 'public');
            
            // Adiciona o caminho do arquivo aos dados que serão salvos no banco.
            $validatedData['arquivo'] = $path;
        }
        
        // 3. ADICIONA UMA DESCRIÇÃO PADRÃO DA ATIVIDADE
        // Usamos o campo 'resultado' para o comentário do aluno e 'descricao_atividade' para um log.
        $validatedData['descricao_atividade'] = 'Entrega de tarefa realizada pelo aluno.';

        // 4. CRIA O REGISTRO NO BANCO DE DADOS
        // Usa create() com os dados já validados e com o caminho do arquivo.
        $item = RegistroTarefa::create($validatedData);

        // Retorna o registro criado como confirmação.
        return response()->json($item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = RegistroTarefa::find($id);
        if($item){
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'RegistroTarefa nao encontrado'],404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = RegistroTarefa::find($id);
        if($item){
            $item->update($request->all());
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'RegistroTarefa nao encontrado'],404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = RegistroTarefa::find($id);
        if($item){
            $item->delete();
            return response()->json($item, 200);
        }
        return response()->json(['erro' => 'RegistroTarefa nao encontrado'],404);
    }
}
