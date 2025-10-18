<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliadorProjeto extends Model
{
    use HasFactory;
    
    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'avaliador_projeto';

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array
     */
    protected $fillable = [
        'id_projeto',
        'id_avaliador',
        'status',
    ];

    /**
     * Obtém o projeto ao qual esta atribuição pertence.
     */
    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }

    /**
     * Obtém o usuário (avaliador) ao qual esta atribuição pertence.
     */
    public function avaliador()
    {
        return $this->belongsTo(Usuario::class, 'id_avaliador', 'id_usuario');
    }

    /**
     * Obtém a avaliação que foi submetida para esta atribuição.
     */
    public function avaliacao()
    {
        return $this->hasOne(AvaliacaoAprendizagem::class, 'id_avaliador_projeto', 'id');
    }
}

