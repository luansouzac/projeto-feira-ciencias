<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoAprendizagem extends Model
{
    use HasFactory;

    protected $table = 'avaliacao_aprendizagens';

    protected $primaryKey = 'id_avaliacao';

    

    protected $fillable = [
        'id_projeto',
        'id_avaliador',
        'nota',
        'comentario',
        'data_avaliacao',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }

    public function avaliador()
    {
        return $this->belongsTo(Usuario::class, 'id_avaliador', 'id_usuario');
    }
}
