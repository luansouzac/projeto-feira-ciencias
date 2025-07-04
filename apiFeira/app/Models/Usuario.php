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

    public $timestamps = false;

    protected $hidden = [
        'senha_hash',
    ];

    public function tipoUsuarios()// Um TipoUsuario tem N Usuários.
    {
        return $this->belongsTo(TipoUsuario::class, 'id_tipo_usuario', 'id_tipo_usuario'); //coluna tipo usario PERTENCE a classe TipoUsuario
    }

    public function registroTarefa()
    {
        return $this->hasMany(RegistroTarefa::class, 'id_responsavel', 'id_usuario');
    }

    public function comentarioPlanejamento()
    {
        return $this->hasMany(ComentarioPlanejamento::class, 'id_orientador', 'id_usuario');
    }

    public function membroEquipe()
    {
        return $this->hasMany(MembroEquipe::class, 'id_usuario', 'id_usuario');
    }

    public function projeto()
    {
        return $this->hasMany(Projeto::class, 'id_responsavel', 'id_usuario');
    }

    public function comentarioDesenvolvimento()
    {
        return $this->hasMany(ComentarioDesenvolvimento::class, 'id_orientador', 'id_usuario');
    }
    
    public function avaliacaoAprendizagem()
    {
        return $this->hasMany(avaliacaoAprendizagem::class, 'id_avaliador', 'id_usuario');
    }
}
