<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComentarioPlanejamento extends Model
{
    use HasFactory;

    protected $table = 'comentario_planejamentos';

    protected $primaryKey = 'id_comentario';

    public $timestamps = false;

    protected $fillable = [
        'id_projeto',
        'id_orientador',
        'comentario',
        'data_comentario',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }

    public function orientador()
    {
        return $this->belongsTo(Usuario::class, 'id_orientador', 'id_usuario');
    }
}
