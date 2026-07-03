<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComissaoRsc extends Model
{
    protected $table = 'comissoes_rsc';

    protected $fillable = [
        'nome',
        'data_instituicao',
        'ato_instituicao',
        'ativa',
    ];

    protected function casts(): array
    {
        return [
            'data_instituicao' => 'date',
            'ativa' => 'boolean',
        ];
    }

    /** @return HasMany<MembroComissaoRsc, $this> */
    public function membros(): HasMany
    {
        return $this->hasMany(MembroComissaoRsc::class);
    }

    /** @return HasMany<AvaliacaoRsc, $this> */
    public function avaliacoes(): HasMany
    {
        return $this->hasMany(AvaliacaoRsc::class);
    }
}
