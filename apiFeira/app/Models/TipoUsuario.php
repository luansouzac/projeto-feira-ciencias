<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoUsuario extends Model
{
    use HasFactory; //preencher model

    protected $table = 'tipo_usuarios';

    protected $fillable = ['id_tipo_usuario', 'tipo'];

    public function id_tipo_usuario()
    {
       return $this->hasMany(Usuario::class); //tipo usuario esta em N Usuarios
    }
}
