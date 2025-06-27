<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembroEquipe extends Model
{
    use HasFactory;

    protected $table = 'membro_equipes';

    protected $primaryKey = 'id_membro';

    protected $fillable = ['id_equipe', 'id_usuario', 'id_funcao'];

    public function funcao()
    {
        return $this->belongsTo(Funcao::class, 'id_funcao', 'id_funcao');
    }
    public function usuario()
    {
        return $this->belongsTo(Usuario::class,'id_usuario','id_usuario');
    }
    public function equipe()
    {
        return $this->belongsTo(Equipe::class,'id_equipe','id_equipe');
    }

    public function atribuicaoTarefa()
    {
        return $this->hasMany(AtribuicaoTarefa::class,'id_membro','id_membro');
    }

}
