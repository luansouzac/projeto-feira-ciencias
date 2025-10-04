<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtribuicaoTarefa extends Model
{
    use HasFactory;

    protected $table = 'atribuicao_tarefas';

    protected $primaryKey = 'id_atribuicao';

    public $timestamps = false;

    protected $fillable = ['id_tarefa', 'id_membro', 'data_atribuicao'];

    public function tarefa()
    {
        return $this->belongsTo(Tarefa::class, 'id_tarefa', 'id_tarefa');
    }
    public function membro()
    {
        return $this->belongsTo(MembroEquipe::class,'id_membro', 'id_membro');
    }
}
