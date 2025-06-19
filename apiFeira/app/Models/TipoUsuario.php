<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoUsuario extends Model
{
    use HasFactory; //preencher model

    protected $table = 'tipo_usuarios';

    protected $primaryKey = 'id_tipo_usuario';

    protected $fillable = ['tipo'];

    public function tipoUsuario()//A Usuario belongs to one TipoUsuario.
    {
       return $this->hasMany(Usuario::class, 'id_tipo_usuario', 'id_tipo_usuario'); //tipo usuario esta em N Usuarios
    }
}
