<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjetivoProjeto extends Model
{
    use HasFactory;

    protected $table = 'objetivo_projetos';

    protected $primaryKey = 'id_objetivo';

    protected $fillable = ['id_projeto', 'descricao'];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }
}
