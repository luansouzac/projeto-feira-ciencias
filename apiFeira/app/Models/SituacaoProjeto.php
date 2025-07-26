<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SituacaoProjeto extends Model
{
    use HasFactory;

    protected $table = 'situacao';

    protected $primaryKey = 'id_situacao';

    public $timestamps = false;

    protected $fillable = ['situacao'];

    public function projeto() //A Projeto belongs to one SituacaoProjeto.
    {
        return $this->hasMany(Projeto::class, 'id_situacao', 'id_situacao'); //situacao esta em N Projetos
    }
    
    public function tarefa()
    {
        return $this->hasMany(Tarefa::class, 'id_situacao', 'id_situacao');
    }
    public function projetoAvaliacao()
    {
        return $this->hasMany(ProjetoAvaliacao::class, 'id_situacao', 'id_situacao');
    }
}
