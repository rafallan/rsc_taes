<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvaliacaoRsc extends Model
{
    protected $table = 'avaliacoes_rsc';

    protected $fillable = [
        'solicitacao_rsc_id',
        'comissao_rsc_id',
        'avaliador_id',
        'data_inicio',
        'data_fim',
        'pontos_declarados',
        'pontos_reconhecidos',
        'criterios_reconhecidos',
        'resultado',
        'parecer_final',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'datetime',
            'data_fim' => 'datetime',
            'pontos_declarados' => 'decimal:2',
            'pontos_reconhecidos' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<SolicitacaoRsc, $this> */
    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRsc::class, 'solicitacao_rsc_id');
    }

    /** @return BelongsTo<ComissaoRsc, $this> */
    public function comissao(): BelongsTo
    {
        return $this->belongsTo(ComissaoRsc::class, 'comissao_rsc_id');
    }

    /** @return BelongsTo<MembroComissaoRsc, $this> */
    public function avaliador(): BelongsTo
    {
        return $this->belongsTo(MembroComissaoRsc::class, 'avaliador_id');
    }

    /** @return HasMany<AvaliacaoRscItem, $this> */
    public function itens(): HasMany
    {
        return $this->hasMany(AvaliacaoRscItem::class);
    }
}
