<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoSolicitacaoRsc extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'historico_solicitacoes_rsc';

    protected $fillable = [
        'solicitacao_rsc_id',
        'usuario_id',
        'status_anterior',
        'status_novo',
        'descricao',
    ];

    /** @return BelongsTo<SolicitacaoRsc, $this> */
    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRsc::class, 'solicitacao_rsc_id');
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
