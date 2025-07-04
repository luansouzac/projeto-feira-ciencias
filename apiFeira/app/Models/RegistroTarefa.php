<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroTarefa extends Model
{
    use HasFactory;

    protected $table = 'registro_tarefas';

    protected $primaryKey = 'id_registro';

    public $timestamps = false;

    protected $fillable = [
        'id_tarefa',
        'descricao_atividade',
        'resultado',
        'data_execucao',
        'arquivo',
        'id_responsavel',
    ];

    public function tarefa()
    {
        return $this->belongsTo(Tarefa::class, 'id_tarefa', 'id_tarefa');
    }

    public function responsavel()
    {
        return $this->belongsTo(Usuario::class, 'id_responsavel', 'id_usuario');
    }

    public function comentarioDesenvolvimento()
    {
        return $this->hasMany(ComentarioDesenvolvimento::class, 'id_registro', 'id_registro');
    }
}
