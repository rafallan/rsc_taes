<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Escolaridade extends Model
{
    protected $fillable = [
        'nome',
        'ordem',
    ];

    /** @return HasMany<NivelRsc, $this> */
    public function niveisRsc(): HasMany
    {
        return $this->hasMany(NivelRsc::class, 'escolaridade_minima_id');
    }

    /** @return HasMany<Servidor, $this> */
    public function servidores(): HasMany
    {
        return $this->hasMany(Servidor::class);
    }
}
