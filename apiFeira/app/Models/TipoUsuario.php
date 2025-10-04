<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class TipoUsuario extends Model
{
    use HasFactory, HasRoles, HasApiTokens; //preencher model

    protected $guard_name = 'sanctum';

    protected $table = 'tipo_usuarios';

    protected $primaryKey = 'id_tipo_usuario';

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    

    protected $fillable = ['tipo'];

    public function tipoUsuario()//A Usuario belongs to one TipoUsuario.
    {
       return $this->hasMany(Usuario::class, 'id_tipo_usuario', 'id_tipo_usuario'); //tipo usuario esta em N Usuarios
    }
}
