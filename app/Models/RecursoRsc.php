<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursoRsc extends Model
{
    protected $table = 'recursos_rsc';

    protected $fillable = [
        'solicitacao_rsc_id',
        'servidor_id',
        'data_recurso',
        'fundamentacao',
        'status',
        'decisao',
        'data_decisao',
    ];

    protected function casts(): array
    {
        return [
            'data_recurso' => 'datetime',
            'data_decisao' => 'datetime',
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
}
