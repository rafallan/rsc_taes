<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembroComissaoRsc extends Model
{
    protected $table = 'membros_comissao_rsc';

    protected $fillable = [
        'comissao_rsc_id',
        'servidor_id',
        'tipo',
        'origem_indicacao',
        'data_inicio_mandato',
        'data_fim_mandato',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio_mandato' => 'date',
            'data_fim_mandato' => 'date',
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<ComissaoRsc, $this> */
    public function comissao(): BelongsTo
    {
        return $this->belongsTo(ComissaoRsc::class, 'comissao_rsc_id');
    }

    /** @return BelongsTo<Servidor, $this> */
    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }

    /** @return HasMany<AvaliacaoRsc, $this> */
    public function avaliacoesRelatadas(): HasMany
    {
        return $this->hasMany(AvaliacaoRsc::class, 'avaliador_id');
    }
}
