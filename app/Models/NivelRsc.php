<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NivelRsc extends Model
{
    protected $table = 'niveis_rsc';

    protected $fillable = [
        'codigo',
        'nome',
        'escolaridade_minima_id',
        'pontos_minimos',
        'criterios_minimos',
        'percentual_iq',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'pontos_minimos' => 'decimal:2',
            'percentual_iq' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Escolaridade, $this> */
    public function escolaridadeMinima(): BelongsTo
    {
        return $this->belongsTo(Escolaridade::class, 'escolaridade_minima_id');
    }

    /** @return BelongsToMany<RequisitoRsc, $this> */
    public function requisitosObrigatorios(): BelongsToMany
    {
        return $this->belongsToMany(RequisitoRsc::class, 'nivel_rsc_requisitos_obrigatorios')
            ->withPivot('tipo_regra')
            ->withTimestamps();
    }

    /** @return HasMany<SolicitacaoRsc, $this> */
    public function solicitacoes(): HasMany
    {
        return $this->hasMany(SolicitacaoRsc::class);
    }
}
