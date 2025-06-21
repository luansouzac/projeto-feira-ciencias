<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipe extends Model
{
    use HasFactory;

    protected $table = 'equipes';
    protected $primaryKey = 'id_equipe';
    protected $fillable = ['id_projeto'];

    public function projetos()
    {
        return $this->hasMany(Projeto::class, 'id_projeto', 'id_projeto');
    }
}
