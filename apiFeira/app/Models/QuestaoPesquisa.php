<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestaoPesquisa extends Model
{
    use HasFactory;

    protected $table = 'questao_pesquisas';

    protected $primaryKey = 'id_questao';

    public $timestamps = false;

    protected $fillable = ['id_projeto', 'descricao'];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }
}
