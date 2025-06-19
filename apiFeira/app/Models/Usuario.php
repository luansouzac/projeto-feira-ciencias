<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    protected $fillable = ['nome', 'email', 'senha_hash', 'data_cadastro','id_tipo_usuario'];

    public function tipoUsuarios()// Um TipoUsuario tem N Usuários.
    {
        return $this->belongsTo(TipoUsuario::class, 'id_tipo_usuario', 'id_tipo_usuario'); //coluna tipo usario PERTENCE a classe TipoUsuario
    }
}
