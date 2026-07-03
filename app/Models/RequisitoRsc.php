<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequisitoRsc extends Model
{
    protected $table = 'requisitos_rsc';

    protected $fillable = [
        'numero',
        'nome',
        'descricao',
    ];

    /** @return HasMany<CriterioRsc, $this> */
    public function criterios(): HasMany
    {
        return $this->hasMany(CriterioRsc::class)->orderBy('item');
    }
}
