<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApresentacaoProjeto extends Model
{
    use HasFactory;

    protected $table = 'apresentacao_projetos';

    protected $primaryKey = 'id_apresentacao';

    public $timestamps = false;

    protected $fillable = [
        'id_projeto',
        'arquivo_pdf',
        'data_envio',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'id_projeto', 'id_projeto');
    }
}
