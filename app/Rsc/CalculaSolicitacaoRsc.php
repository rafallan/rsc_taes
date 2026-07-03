<?php

namespace App\Rsc;

use App\Models\CriterioRsc;
use App\Models\NivelRsc;
use App\Models\Servidor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalculaSolicitacaoRsc
{
    /**
     * @param  array<int, array<string, mixed>>  $atividades
     * @return array{
     *     pontos: float,
     *     criterios: int,
     *     requisito_especial_atendido: bool,
     *     bloqueios: array<int, string>,
     *     apta: bool,
     *     itens: array<int, array<string, mixed>>
     * }
     */
    public function calcular(
        NivelRsc $nivel,
        Servidor $servidor,
        array $atividades,
        ?string $memorial,
        bool $declaracaoVeracidade,
        bool $declaracaoNaoReutilizacao,
    ): array {
        $nivel->loadMissing(['escolaridadeMinima', 'requisitosObrigatorios']);
        $servidor->loadMissing(['escolaridade', 'ultimaConcessao']);

        $criterios = CriterioRsc::query()
            ->with(['requisito', 'variacoesPontuacao'])
            ->whereIn('id', collect($atividades)->pluck('criterio_rsc_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $itens = collect($atividades)
            ->map(fn (array $atividade): array => $this->normalizarAtividade($atividade, $criterios))
            ->filter(fn (array $item): bool => $item['criterio'] instanceof CriterioRsc)
            ->values();

        $pontos = round($itens->sum('pontos_calculados'), 2);
        $criteriosDistintos = $itens->pluck('criterio_rsc_id')->unique()->count();
        $requisitosUsados = $itens->pluck('criterio.requisito_rsc_id')->unique();
        $requisitosObrigatorios = $nivel->requisitosObrigatorios->pluck('id');
        $requisitoEspecialAtendido = $requisitosObrigatorios->isEmpty() || $requisitosObrigatorios->intersect($requisitosUsados)->isNotEmpty();

        $bloqueios = $this->bloqueiosGerais(
            nivel: $nivel,
            servidor: $servidor,
            pontos: $pontos,
            criteriosDistintos: $criteriosDistintos,
            requisitoEspecialAtendido: $requisitoEspecialAtendido,
            memorial: $memorial,
            declaracaoVeracidade: $declaracaoVeracidade,
            declaracaoNaoReutilizacao: $declaracaoNaoReutilizacao,
            itens: $itens->all(),
        );

        return [
            'pontos' => $pontos,
            'criterios' => $criteriosDistintos,
            'requisito_especial_atendido' => $requisitoEspecialAtendido,
            'bloqueios' => $bloqueios,
            'apta' => $bloqueios === [],
            'itens' => $itens->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $atividade
     * @param  Collection<int, CriterioRsc>  $criterios
     * @return array<string, mixed>
     */
    private function normalizarAtividade(array $atividade, Collection $criterios): array
    {
        $criterio = $criterios->get((int) ($atividade['criterio_rsc_id'] ?? 0));
        $variacao = $criterio?->variacoesPontuacao->firstWhere('id', (int) ($atividade['variacao_pontuacao_id'] ?? 0));
        $quantidade = (float) ($atividade['quantidade'] ?? 0);
        $pontosUnitarios = match (true) {
            $variacao !== null => (float) $variacao->pontos,
            $criterio !== null => (float) $criterio->pontos,
            default => 0,
        };

        return [
            ...$atividade,
            'criterio' => $criterio,
            'criterio_rsc_id' => $criterio?->id,
            'variacao_pontuacao_id' => $variacao?->id,
            'pontos_unitarios' => $pontosUnitarios,
            'pontos_calculados' => round($quantidade * $pontosUnitarios, 2),
            'tem_documentos' => is_countable($atividade['documentos'] ?? null) && count($atividade['documentos']) > 0,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     * @return array<int, string>
     */
    private function bloqueiosGerais(
        NivelRsc $nivel,
        Servidor $servidor,
        float $pontos,
        int $criteriosDistintos,
        bool $requisitoEspecialAtendido,
        ?string $memorial,
        bool $declaracaoVeracidade,
        bool $declaracaoNaoReutilizacao,
        array $itens,
    ): array {
        $bloqueios = [];

        if ($servidor->estagio_probatorio) {
            $bloqueios[] = 'Servidor em estágio probatório não pode submeter solicitação de RSC-PCCTAE.';
        }

        $dataUltimaConcessao = $servidor->ultimaConcessao?->data_deferimento;

        if ($dataUltimaConcessao && Carbon::parse($dataUltimaConcessao)->addYears(3)->isFuture()) {
            $bloqueios[] = 'Ainda não foi cumprido o interstício de três anos desde a última concessão.';
        }

        if ($servidor->escolaridade && $servidor->escolaridade->ordem < $nivel->escolaridadeMinima->ordem) {
            $bloqueios[] = 'A escolaridade informada não atende à escolaridade formal do nível pleiteado.';
        }

        if (blank($memorial)) {
            $bloqueios[] = 'O memorial descritivo é obrigatório.';
        }

        if (! $declaracaoVeracidade || ! $declaracaoNaoReutilizacao) {
            $bloqueios[] = 'As declarações obrigatórias precisam ser confirmadas.';
        }

        $itens = collect($itens);

        if ($itens->isEmpty()) {
            $bloqueios[] = 'Informe ao menos uma atividade ou experiência.';
        }

        if ($itens->contains(fn (array $item): bool => ! $item['tem_documentos'])) {
            $bloqueios[] = 'Cada atividade declarada precisa ter documentação comprobatória.';
        }

        if ($itens->contains(fn (array $item): bool => ! (bool) ($item['atividade_exercicio_cargo'] ?? false))) {
            $bloqueios[] = 'Todas as atividades devem ter ocorrido no exercício do cargo.';
        }

        if ($itens->contains(fn (array $item): bool => (bool) ($item['atividade_ordinaria_cargo'] ?? false))) {
            $bloqueios[] = 'Atividades exclusivamente ordinárias do cargo não podem compor a pontuação.';
        }

        if ($itens->contains(fn (array $item): bool => (bool) ($item['usado_em_concessao_anterior'] ?? false))) {
            $bloqueios[] = 'Atividades já usadas em concessão anterior não podem ser reutilizadas.';
        }

        if ($pontos < (float) $nivel->pontos_minimos) {
            $bloqueios[] = "Pontuação insuficiente para {$nivel->nome}: mínimo de {$nivel->pontos_minimos} pontos.";
        }

        if ($criteriosDistintos < $nivel->criterios_minimos) {
            $bloqueios[] = "Quantidade insuficiente de critérios específicos: mínimo de {$nivel->criterios_minimos}.";
        }

        if (! $requisitoEspecialAtendido) {
            $bloqueios[] = 'A solicitação não possui critério no requisito obrigatório especial do nível pleiteado.';
        }

        return array_values(array_unique($bloqueios));
    }
}
