<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questionario extends Model
{
    use HasFactory;

    protected $table = 'questionarios';
    protected $primaryKey = 'id_questionario';

    protected $fillable = [
        'id_evento',
        'titulo',
        'ativo',
    ];

    /**
     * Um questionário pertence a um evento.
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'id_evento', 'id_evento');
    }

    /**
     * Um questionário tem muitas perguntas.
     */
    public function perguntas()
    {
        return $this->hasMany(PerguntaQuestionario::class, 'id_questionario', 'id_questionario');
    }
}
