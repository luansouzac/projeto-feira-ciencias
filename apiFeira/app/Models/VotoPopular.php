<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VotoPopular extends Model
{
    use HasFactory;

    protected $table = 'votos_populares';
    protected $primaryKey = 'id_voto';

    protected $fillable = [
        'id_projeto',
        'id_usuario',
    ];

    /**
     * Um voto pertence a um projeto.
     */
    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }

    /**
     * Um voto pertence a um usuário (o aluno que votou).
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
