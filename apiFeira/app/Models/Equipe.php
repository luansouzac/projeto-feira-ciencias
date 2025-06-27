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

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }

    public function membroEquipe()
    {
        return $this->hasMany(MembroEquipe::class, 'id_equipe', 'id_equipe');
    }
}
