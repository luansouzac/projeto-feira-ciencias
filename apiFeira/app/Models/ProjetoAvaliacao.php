<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjetoAvaliacao extends Model
{
    use HasFactory;

    protected $table = 'projeto_avaliacoes';

    protected $primaryKey = 'id_projeto_avaliacao';

    public $timestamps = false;

    protected $fillable = [
        'id_projeto',
        'id_avaliador',
        'id_situacao',
        'feedback',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }
    
    public function avaliador()
    {
        return $this->belongsTo(Usuario::class, 'id_avaliador', 'id_usuario');
    }
    public function situacao()
    {
        return $this->belongsTo(SituacaoProjeto::class, 'id_situacao', 'id_situacao');
    }

}
