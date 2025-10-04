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
        'id_evento',
        'id_orientador',
        'id_coorientador',
        'max_pessoas'
        ];

        public function responsavel() //Um Projeto PERTENCE a um Usuário.
        {
            return $this->belongsTo(Usuario::class, 'id_responsavel', 'id_usuario'); //coluna id_responsavel PERTENCE a classe Usuario
        }// uma projeto pertence a um respnsevel (mas cho que seria equipe, nao?)

        public function orientador()
        {
            return $this->belongsTo(Usuario::class, 'id_orientador', 'id_usuario');
        }
        public function coorientador()
        {
            return $this->belongsTo(Usuario::class, 'id_coorientador', 'id_usuario');
        }

        public function situacao()// Um Projeto PERTENCE a uma SituacaoProjeto.
        {
            return $this->belongsTo(SituacaoProjeto::class, 'id_situacao', 'id_situacao'); //coluna id_situacao PERTENCE a classe Usuario
        }

        public function tarefa()
        {
            return $this->hasMany(Projeto::class, 'id_projeto', 'id_projeto');
        }

        public function comentarioPlanejamento()
        {
            return $this->hasMany(ComentarioPlanejamento::class, 'id_projeto', 'id_projeto'); 
        }

        public function equipe()
        {
            return $this->hasMany(Equipe::class, 'id_projeto', 'id_projeto'); 
        }
        
        public function apresentacaoProjeto()
        {
            return $this->hasMany(ApresentacaoProjeto::class, 'id_projeto', 'id_projeto'); 
        }
        
        public function discussaoEquipe()
        {
            return $this->hasMany(DiscussaoEquipe::class, 'id_projeto', 'id_projeto'); 
        }

        public function avaliacaoAprendizagem()
        {
            return $this->hasMany(avaliacaoAprendizagem::class, 'id_projeto', 'id_projeto'); 
        }

        public function questaoPesquisa()
        {
            return $this->hasMany(questaoPesquisa::class, 'id_projeto', 'id_projeto'); 
        }

        public function objetivoProjeto()
        {
            return $this->hasMany(ObjetivoProjeto::class, 'id_projeto', 'id_projeto'); 
        }

        public function eventos()
        {
            return $this->belongsTo(Evento::class, 'id_evento', 'id_evento');
        }
        public function projetoAvaliacao()
        {
            return $this->hasMany(ProjetoAvaliacao::class, 'id_projeto', 'id_projeto');
        }
}
