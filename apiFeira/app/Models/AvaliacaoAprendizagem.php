<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoAprendizagem extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'avaliacoes';

    /**
     * A chave primária da tabela.
     *
     * @var string
     */
    protected $primaryKey = 'id_avaliacao';

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array
     */
    protected $fillable = [
        // ✅ CORREÇÃO: A avaliação agora é preenchida com o ID da atribuição,
        // e não mais com os IDs diretos do projeto e do avaliador.
        'id_avaliador_projeto',
        'nota_geral',
        'observacoes',
    ];


    public function atribuicao()
    {
        return $this->belongsTo(AvaliadorProjeto::class, 'id_avaliador_projeto', 'id');
    }
    
    /**
     * Obtém todas as respostas individuais desta avaliação.
     * (Este relacionamento permanece igual)
     */
    public function respostas()
    {
        return $this->hasMany(RespostaAvaliacao::class, 'id_avaliacao', 'id_avaliacao');
    }

    // --- RELACIONAMENTOS INDIRETOS (ATALHOS) ---

    /**
     * Obtém o projeto desta avaliação através da tabela de atribuição.
     * Permite aceder facilmente com `$avaliacao->projeto`.
     */
    public function projeto()
    {
        return $this->hasOneThrough(
            Projeto::class,          
            AvaliadorProjeto::class, 
            'id',                    
            'id_projeto',            // Chave estrangeira em `avaliador_projeto` -> `projetos`.id_projeto
            'id_avaliador_projeto',  // Chave local em `avaliacoes`
            'id_projeto'             // Chave local em `avaliador_projeto`
        );
    }

    /**
     * Obtém o avaliador desta avaliação através da tabela de atribuição.
     * Permite aceder facilmente com `$avaliacao->avaliador`.
     */
    public function avaliador()
    {
        return $this->hasOneThrough(
            Usuario::class,          // O modelo final que queremos
            AvaliadorProjeto::class, // O modelo intermediário
            'id',                    // Chave estrangeira em `avaliacoes` -> `avaliador_projeto`.id
            'id_usuario',            // Chave estrangeira em `avaliador_projeto` -> `usuarios`.id_usuario
            'id_avaliador_projeto',  // Chave local em `avaliacoes`
            'id_avaliador'           // Chave local em `avaliador_projeto`
        );
    }
}
