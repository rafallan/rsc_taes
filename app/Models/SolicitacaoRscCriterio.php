<?php

namespace App\Models;

use App\Rsc\AvaliacaoItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitacaoRscCriterio extends Model
{
    protected $table = 'solicitacao_rsc_criterios';

    protected $fillable = [
        'solicitacao_rsc_id',
        'criterio_rsc_id',
        'variacao_pontuacao_id',
        'titulo_atividade',
        'descricao_atividade',
        'data_inicio',
        'data_fim',
        'quantidade',
        'pontos_unitarios',
        'pontos_calculados',
        'atividade_exercicio_cargo',
        'atividade_ordinaria_cargo',
        'justificativa_relevancia',
        'usado_em_concessao_anterior',
        'status_avaliacao',
        'pontos_aceitos',
        'parecer_avaliador',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'quantidade' => 'decimal:2',
            'pontos_unitarios' => 'decimal:2',
            'pontos_calculados' => 'decimal:2',
            'atividade_exercicio_cargo' => 'boolean',
            'atividade_ordinaria_cargo' => 'boolean',
            'usado_em_concessao_anterior' => 'boolean',
            'status_avaliacao' => AvaliacaoItemStatus::class,
            'pontos_aceitos' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<SolicitacaoRsc, $this> */
    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRsc::class, 'solicitacao_rsc_id');
    }

    /** @return BelongsTo<CriterioRsc, $this> */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(CriterioRsc::class, 'criterio_rsc_id');
    }

    /** @return BelongsTo<CriterioRscVariacaoPontuacao, $this> */
    public function variacaoPontuacao(): BelongsTo
    {
        return $this->belongsTo(CriterioRscVariacaoPontuacao::class, 'variacao_pontuacao_id');
    }

    /** @return HasMany<DocumentoComprobatorio, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoComprobatorio::class);
    }
}
