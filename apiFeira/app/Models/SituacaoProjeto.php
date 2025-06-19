<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SituacaoProjeto extends Model
{
    use HasFactory;

    protected $table = 'situacao';

    protected $primaryKey = 'id_situacao';

    protected $fillable = ['situacao'];

    public function situacao() //A Projeto belongs to one SituacaoProjeto.
    {
        return $this->hasMany(Projetos::class, 'id_situacao', 'id_situacao'); //situacao esta em N Projetos
    }
    
}
