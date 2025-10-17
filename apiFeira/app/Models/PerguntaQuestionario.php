<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerguntaQuestionario extends Model
{
    use HasFactory;

    protected $table = 'perguntas_questionario';
    protected $primaryKey = 'id_pergunta';

    protected $fillable = [
        'id_questionario',
        'criterio',
        'texto_pergunta',
        'ordem',
    ];

    /**
     * Uma pergunta pertence a um questionário.
     */
    public function questionario()
    {
        return $this->belongsTo(Questionario::class, 'id_questionario', 'id_questionario');
    }
}
