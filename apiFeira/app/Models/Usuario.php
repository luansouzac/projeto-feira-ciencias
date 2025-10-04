<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'sanctum';

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    protected $fillable = ['nome', 'email', 'senha_hash', 'data_cadastro','id_tipo_usuario', 'id_matricula', 'cpf', 'telefone', 'instituicao', 'ano', 'photo'];

    public $timestamps = false;

    protected $hidden = [
        'senha_hash',
    ];

    protected $with =[
        'tipoUsuario'
    ];

    public function getAuthPassword()
    {
        return $this->senha_hash;
    }
    public function tipoUsuario()// Um TipoUsuario tem N Usuários.
    {
        return $this->belongsTo(TipoUsuario::class, 'id_tipo_usuario', 'id_tipo_usuario'); //coluna tipo usario PERTENCE a classe TipoUsuario
    }
    public function orientador()
    {
        return $this->hasMany(Projeto::class, 'id_orientador', 'id_usuario');
    }
    public function coorientador()
    {
        return $this->hasMany(Projeto::class, 'id_coorientador', 'id_usuario');
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
    public function avaliacaoProjeto()
    {
        return $this->hasMany(ProjetoAvaliacao::class, 'id_avaliador', 'id_usuario');
    }

    public function can($ability, $arguments = []): bool
    {
        if (parent::can($ability, $arguments)) {
            return true;
        }

        if ($this->tipoUsuario && $this->tipoUsuario->can($ability, $arguments)) {
            return true;
        }

        return false;
    }

    public function hasRole($roles, string $guard = null): bool
    {
        // Verifica se o tipoUsuario existe e se ele tem a role
        if ($this->tipoUsuario && $this->tipoUsuario->hasRole($roles, $guard)) {
            return true;
        }

        return false;
    }


    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $permissions = collect();

        // Obter as permissões diretas que o *usuário* possui (se aplicável ao seu design)
        $userDirectPermissions = $this->permissions->pluck('name');

        $permissions = $permissions->merge($userDirectPermissions);


        // Se o tipoUsuario existe, adiciona as permissões do tipoUsuario
        if ($this->tipoUsuario) {
            // O TipoUsuario precisa ter o HasRoles para que this->tipoUsuario->getAllPermissions() funcione.
            $permissions = $permissions->merge($this->tipoUsuario->getAllPermissions());
        }

        return $permissions->unique(); // Retorna permissões únicas
    }

    public function getRoleNames(): \Illuminate\Support\Collection
    {
        $roleNames = collect();

        // Pega os nomes das roles diretas do próprio usuário (se houver alguma)
        $userDirectRoleNames = $this->roles->pluck('name');
        $roleNames = $roleNames->merge($userDirectRoleNames);


        // Se o tipoUsuario existe, adiciona os nomes das roles do tipoUsuario
        if ($this->tipoUsuario) {
            // O TipoUsuario precisa ter o HasRoles para que this->tipoUsuario->getRoleNames() funcione.
            $roleNames = $roleNames->merge($this->tipoUsuario->getRoleNames());
        }

        return $roleNames->unique(); // Retorna nomes de roles únicos
    }
}
