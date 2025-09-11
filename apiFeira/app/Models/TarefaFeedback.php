<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarefaFeedback extends Model
{
    use HasFactory;

    protected $table = 'tarefa_feedbacks';
    protected $primaryKey = 'id_feedback';

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
    public function feedbacks()
    {
        return $this->hasMany(TarefaFeedback::class, 'id_tarefa', 'id_tarefa');
    }
}
