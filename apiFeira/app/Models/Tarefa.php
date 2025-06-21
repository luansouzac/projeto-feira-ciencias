<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model
{
    use HasFactory;

    protected $table = 'tarefas';

    protected $primaryKey = 'id_tarefa';

    protected $fillable = [
        'id_projeto', 
        'descricao', 
        'detalhe',
        'id_situacao', 
        'data_inicio_prevista',
        'data_fim_prevista',
        'data_conclusao'
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }
    public function situacao(){
        return $this->belongsTo(SituacaoProjeto::class,'id_situacao','id_situacao');
    }
}
