<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Servidor extends Model
{
    protected $table = 'servidores';

    protected $fillable = [
        'user_id',
        'escolaridade_id',
        'nome',
        'siape',
        'cpf',
        'email_institucional',
        'cargo',
        'unidade_lotacao',
        'data_ingresso_cargo',
        'estagio_probatorio',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_ingresso_cargo' => 'date',
            'estagio_probatorio' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Escolaridade, $this> */
    public function escolaridade(): BelongsTo
    {
        return $this->belongsTo(Escolaridade::class);
    }

    /** @return HasMany<SolicitacaoRsc, $this> */
    public function solicitacoes(): HasMany
    {
        return $this->hasMany(SolicitacaoRsc::class);
    }

    /** @return HasOne<ConcessaoRsc, $this> */
    public function ultimaConcessao(): HasOne
    {
        return $this->hasOne(ConcessaoRsc::class)->latestOfMany('data_deferimento');
    }
}
