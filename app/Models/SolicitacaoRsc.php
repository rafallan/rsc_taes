<?php

namespace App\Models;

use App\Rsc\SolicitacaoRscStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolicitacaoRsc extends Model
{
    protected $table = 'solicitacoes_rsc';

    protected $fillable = [
        'servidor_id',
        'nivel_rsc_id',
        'numero_protocolo',
        'status',
        'data_abertura',
        'data_submissao',
        'saldo_pontos_anterior',
        'pontos_declarados',
        'criterios_declarados',
        'memorial',
        'declaracao_veracidade',
        'declaracao_nao_reutilizacao',
    ];

    protected $attributes = [
        'status' => SolicitacaoRscStatus::Rascunho->value,
        'saldo_pontos_anterior' => 0,
        'pontos_declarados' => 0,
        'criterios_declarados' => 0,
        'declaracao_veracidade' => false,
        'declaracao_nao_reutilizacao' => false,
    ];

    protected function casts(): array
    {
        return [
            'status' => SolicitacaoRscStatus::class,
            'data_abertura' => 'datetime',
            'data_submissao' => 'datetime',
            'saldo_pontos_anterior' => 'decimal:2',
            'pontos_declarados' => 'decimal:2',
            'declaracao_veracidade' => 'boolean',
            'declaracao_nao_reutilizacao' => 'boolean',
        ];
    }

    /** @return BelongsTo<Servidor, $this> */
    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }

    /** @return BelongsTo<NivelRsc, $this> */
    public function nivelRsc(): BelongsTo
    {
        return $this->belongsTo(NivelRsc::class);
    }

    /** @return HasMany<SolicitacaoRscCriterio, $this> */
    public function criterios(): HasMany
    {
        return $this->hasMany(SolicitacaoRscCriterio::class);
    }

    /** @return HasMany<HistoricoSolicitacaoRsc, $this> */
    public function historicos(): HasMany
    {
        return $this->hasMany(HistoricoSolicitacaoRsc::class);
    }

    /** @return HasOne<ConcessaoRsc, $this> */
    public function concessao(): HasOne
    {
        return $this->hasOne(ConcessaoRsc::class);
    }
}
