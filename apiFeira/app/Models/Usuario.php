<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $fillable = ['id_usuario', 'nome', 'email', 'senha_hash', 'id_tipo_usuario'];

    public function id_tipo_usuario()
    {
        return $this->belongsTo(TipoUsuario::class); //coluna tipo usario PERTENCE a classe TipoUsuario
    }
}
