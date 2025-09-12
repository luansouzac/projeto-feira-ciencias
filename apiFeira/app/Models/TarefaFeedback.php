<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarefaFeedback extends Model
{
    use HasFactory;

    /**
     * ✅ CORREÇÃO: Informa ao Laravel o nome correto da tabela.
     * O nome da sua tabela é 'tarefa_feedback' (singular), então especificamos aqui.
     */
    protected $table = 'tarefa_feedback';

    /**
     * A chave primária da tabela.
     */
    protected $primaryKey = 'id_tarefa_feedback'; // Ajustado para corresponder à sua coluna

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'id_tarefa',
        'id_usuario',
        'feedback',
    ];

    /**
     * Define o relacionamento: um feedback pertence a uma tarefa.
     */
    public function tarefa()
    {
        return $this->belongsTo(Tarefa::class, 'id_tarefa', 'id_tarefa');
    }

    /**
     * Define o relacionamento: um feedback pertence a um usuário.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}

