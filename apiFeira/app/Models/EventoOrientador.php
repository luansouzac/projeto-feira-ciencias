<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoOrientador extends Model
{
    use HasFactory;

    protected $table = 'evento_orientador';
    protected $primaryKey = 'id_evento_orientador';

    public $timestamps = false;

    protected $fillable = [
        'id_evento',
        'id_orientador',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'id_evento', 'id_evento');
    }
    public function orientador()
    {
        return $this->belongsTo(Usuario::class, 'id_orientador', 'id_usuario');
    }
}
