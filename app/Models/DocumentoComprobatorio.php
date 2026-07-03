<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoComprobatorio extends Model
{
    protected $table = 'documentos_comprobatorios';

    protected $fillable = [
        'solicitacao_rsc_criterio_id',
        'tipo_documento',
        'nome_original',
        'caminho_arquivo',
        'mime_type',
        'tamanho',
        'observacao',
        'validado',
        'parecer',
    ];

    protected function casts(): array
    {
        return [
            'validado' => 'boolean',
        ];
    }

    /** @return BelongsTo<SolicitacaoRscCriterio, $this> */
    public function solicitacaoCriterio(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRscCriterio::class, 'solicitacao_rsc_criterio_id');
    }
}
