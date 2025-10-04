<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComentarioDesenvolvimento extends Model
{
    use HasFactory;

    protected $table = 'comentario_desenvolvimentos';

    protected $primaryKey = 'id_comentario';

    public $timestamps = false;

    protected $fillable = [
        'id_registro',
        'id_orientador',
        'comentario',
        'data_comentario',
    ];

    public function registro()
    {
        return $this->belongsTo(RegistroTarefa::class, 'id_registro', 'id_registro');
    }

    public function orientador()
    {
        return $this->belongsTo(Usuario::class, 'id_orientador', 'id_usuario');
    }
}