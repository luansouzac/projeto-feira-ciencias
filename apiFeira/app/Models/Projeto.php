<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Projeto extends Model
{
    use HasFactory;

    protected $table = 'projetos';
    protected $primaryKey = 'id_projeto';

    public $timestamps = false; 

    protected $fillable = [
        'id_responsavel',
        'titulo',
        'problema',
        'relevancia',
        'id_situacao',
        'id_evento',
        'data_criacao',
        'data_aprovacao',
        'id_orientador',
        'id_coorientador',
        'max_pessoas' 
    ];
    
    public function responsavel()
    {
        return $this->belongsTo(Usuario::class, 'id_responsavel', 'id_usuario');
    }

    public function orientador()
    {
        return $this->belongsTo(Usuario::class, 'id_orientador', 'id_usuario');
    }

    public function coorientador()
    {
        return $this->belongsTo(Usuario::class, 'id_coorientador', 'id_usuario');
    }

    public function situacao()
    {
        return $this->belongsTo(SituacaoProjeto::class, 'id_situacao', 'id_situacao');
    }

    public function eventos()
    {
        return $this->belongsTo(Evento::class, 'id_evento', 'id_evento');
    }

    public function equipe()
    {
        return $this->hasOne(Equipe::class, 'id_projeto', 'id_projeto');
    }

    public function membroEquipe()
    {
        return $this->hasManyThrough(
            MembroEquipe::class, 
            Equipe::class,       
            'id_projeto',    
            'id_equipe',      
            'id_projeto',     
            'id_equipe'       
        );
    }

    public function tarefa()
    {
        return $this->hasMany(Tarefa::class, 'id_projeto', 'id_projeto');
    }

    public function comentarioPlanejamento()
    {
        return $this->hasMany(ComentarioPlanejamento::class, 'id_projeto', 'id_projeto'); 
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
        return $this->hasMany(AvaliacaoAprendizagem::class, 'id_projeto', 'id_projeto'); 
    }

    public function questaoPesquisa()
    {
        return $this->hasMany(QuestaoPesquisa::class, 'id_projeto', 'id_projeto'); 
    }

    public function objetivoProjeto()
    {
        return $this->hasMany(ObjetivoProjeto::class, 'id_projeto', 'id_projeto'); 
    }
    
    public function projetoAvaliacao()
    {
        return $this->hasMany(ProjetoAvaliacao::class, 'id_projeto', 'id_projeto');
    }
}

