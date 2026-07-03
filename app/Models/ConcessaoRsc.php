<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcessaoRsc extends Model
{
    protected $table = 'concessoes_rsc';

    protected $fillable = [
        'solicitacao_rsc_id',
        'servidor_id',
        'nivel_rsc_id',
        'data_deferimento',
        'data_efeito_financeiro',
        'pontos_utilizados',
        'saldo_pontos',
        'ato_concessao',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_deferimento' => 'date',
            'data_efeito_financeiro' => 'date',
            'pontos_utilizados' => 'decimal:2',
            'saldo_pontos' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<SolicitacaoRsc, $this> */
    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRsc::class, 'solicitacao_rsc_id');
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
}
