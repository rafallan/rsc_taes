<?php

namespace App\Rsc;

enum AvaliacaoItemStatus: string
{
    case Aceito = 'ACEITO';
    case Recusado = 'RECUSADO';
    case ParcialmenteAceito = 'PARCIALMENTE_ACEITO';

    public function label(): string
    {
        return match ($this) {
            self::Aceito => 'Aceito',
            self::Recusado => 'Recusado',
            self::ParcialmenteAceito => 'Parcialmente aceito',
        };
    }
}
