<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterioRscVariacaoPontuacao extends Model
{
    protected $table = 'criterio_rsc_variacoes_pontuacao';

    protected $fillable = [
        'criterio_rsc_id',
        'nome',
        'pontos',
    ];

    protected function casts(): array
    {
        return [
            'pontos' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<CriterioRsc, $this> */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(CriterioRsc::class, 'criterio_rsc_id');
    }
}
