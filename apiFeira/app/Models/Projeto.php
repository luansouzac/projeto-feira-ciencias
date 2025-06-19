<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projeto extends Model
{
    use HasFactory;

    protected $table = 'projetos';

    protected $primaryKey = 'id_projeto';

    protected $fillable = [
        'id_responsavel',
        'titulo',
        'problema',
        'relevancia',
        'id_situacao',
        'data_criacao',
        'data_aprovacao',
        ];

        public function responsavel() //Um Projeto PERTENCE a um Usuário.
        {
            return $this->belongsTo(Usuario::class, 'id_responsavel', 'id_usuario'); //coluna id_responsavel PERTENCE a classe Usuario
        }
        public function situacao()// Um Projeto PERTENCE a uma SituacaoProjeto.
        {
            return $this->belongsTo(SituacaoProjeto::class, 'id_situacao', 'id_situacao'); //coluna id_situacao PERTENCE a classe Usuario
        }
}
