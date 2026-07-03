<?php

namespace App\Rsc;

enum SolicitacaoRscStatus: string
{
    case Rascunho = 'RASCUNHO';
    case AptaParaSubmissao = 'APTA_PARA_SUBMISSAO';
    case Submetida = 'SUBMETIDA';
    case EmAnalise = 'EM_ANALISE';
    case EmDiligencia = 'EM_DILIGENCIA';
    case Deferida = 'DEFERIDA';
    case Indeferida = 'INDEFERIDA';
    case EmRecurso = 'EM_RECURSO';
    case Concedida = 'CONCEDIDA';
    case Cancelada = 'CANCELADA';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::AptaParaSubmissao => 'Apta para submissão',
            self::Submetida => 'Submetida',
            self::EmAnalise => 'Em análise',
            self::EmDiligencia => 'Em diligência',
            self::Deferida => 'Deferida',
            self::Indeferida => 'Indeferida',
            self::EmRecurso => 'Em recurso',
            self::Concedida => 'Concedida',
            self::Cancelada => 'Cancelada',
        };
    }
}
