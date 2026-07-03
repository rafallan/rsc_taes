<?php

namespace App\Models;

use App\Rsc\AvaliacaoItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvaliacaoRscItem extends Model
{
    protected $table = 'avaliacao_rsc_itens';

    protected $fillable = [
        'avaliacao_rsc_id',
        'solicitacao_rsc_criterio_id',
        'status',
        'pontos_solicitados',
        'pontos_aceitos',
        'motivo_recusa',
        'fundamentacao',
    ];

    protected function casts(): array
    {
        return [
            'status' => AvaliacaoItemStatus::class,
            'pontos_solicitados' => 'decimal:2',
            'pontos_aceitos' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<AvaliacaoRsc, $this> */
    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(AvaliacaoRsc::class, 'avaliacao_rsc_id');
    }

    /** @return BelongsTo<SolicitacaoRscCriterio, $this> */
    public function solicitacaoCriterio(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRscCriterio::class, 'solicitacao_rsc_criterio_id');
    }
}
