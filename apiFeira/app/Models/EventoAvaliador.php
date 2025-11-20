<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoAvaliador extends Model
{
    use HasFactory;

    protected $table = 'evento_avaliador';
    protected $primaryKey = 'id_evento_avaliador';

    public $timestamps = false;

    protected $fillable = [
        'id_evento',
        'id_avaliador',
    ];
    public function evento()
    {
        return $this->belongsTo(Evento::class, 'id_evento', 'id_evento');
    }
    public function avaliador()
    {
        return $this->belongsTo(Usuario::class, 'id_avaliador', 'id_usuario');
    }
}
