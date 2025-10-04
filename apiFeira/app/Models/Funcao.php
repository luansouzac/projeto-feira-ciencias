<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funcao extends Model
{
    use HasFactory;

    protected $table = 'funcao';

    protected $primaryKey = 'id_funcao';

    public $timestamps = false;

    protected $fillable = ['funcao'];

    public function membroEquipe()
    {
        return $this->hasMany(MembroEquipe::class, 'id_funcao', 'id_funcao');
    }
}
