<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussaoEquipe extends Model
{
    use HasFactory;

    protected $table = 'discussao_equipes';

    protected $primaryKey = 'id_discussao';

    protected $fillable = [
        'id_projeto',
        'pontos_fortes',
        'pontos_fracos',
        'trabalhos_futuros',
        'data_registro',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }
}
