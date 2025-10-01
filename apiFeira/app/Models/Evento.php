<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'eventos';

    protected $primaryKey = 'id_evento';

    protected $fillable = [
        'nome',
        'ativo',
        'data_evento',
        'inicio_submissao',
        'fim_submissao',
        'inicio_inscricao',
        'fim_inscricao',
        'min_pessoas',
        'max_pessoas'
    ];

    public function projetos()
    {
        return $this->hasMany(Projeto::class, 'id_evento', 'id_evento');
    }
}
