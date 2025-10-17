<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoAprendizagem extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';
    protected $primaryKey = 'id_avaliacao';

    protected $fillable = [
        'id_projeto',
        'id_avaliador',
        'nota_geral',
        'observacoes',
    ];

    /**
     * Uma avaliação pertence a um projeto.
     */
    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }

    /**
     * Uma avaliação pertence a um usuário (o avaliador).
     */
    public function avaliador()
    {
        return $this->belongsTo(Usuario::class, 'id_avaliador', 'id_usuario');
    }

    /**
     * Uma avaliação tem muitas respostas.
     */
    public function respostas()
    {
        return $this->hasMany(RespostaAvaliacao::class, 'id_avaliacao', 'id_avaliacao');
    }
}
