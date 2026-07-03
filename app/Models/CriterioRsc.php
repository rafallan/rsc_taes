<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CriterioRsc extends Model
{
    protected $table = 'criterios_rsc';

    protected $fillable = [
        'requisito_rsc_id',
        'item',
        'descricao',
        'unidade_medida',
        'pontos',
        'permite_multiplicacao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'pontos' => 'decimal:2',
            'permite_multiplicacao' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<RequisitoRsc, $this> */
    public function requisito(): BelongsTo
    {
        return $this->belongsTo(RequisitoRsc::class, 'requisito_rsc_id');
    }

    /** @return HasMany<CriterioRscVariacaoPontuacao, $this> */
    public function variacoesPontuacao(): HasMany
    {
        return $this->hasMany(CriterioRscVariacaoPontuacao::class);
    }
}
