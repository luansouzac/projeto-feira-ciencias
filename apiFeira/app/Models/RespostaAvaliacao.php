<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespostaAvaliacao extends Model
{
    use HasFactory;

    protected $table = 'respostas_avaliacao';
    protected $primaryKey = 'id_resposta';

    protected $fillable = [
        'id_avaliacao',
        'id_pergunta',
        'valor_resposta',
    ];

    /**
     * Uma resposta pertence a uma avaliação.
     */
    public function avaliacao()
    {
        return $this->belongsTo(AvaliacaoAprendizagem::class, 'id_avaliacao', 'id_avaliacao');
    }

    /**
     * Uma resposta refere-se a uma pergunta específica do questionário.
     */
    public function pergunta()
    {
        return $this->belongsTo(PerguntaQuestionario::class, 'id_pergunta', 'id_pergunta');
    }
}
