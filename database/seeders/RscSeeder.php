<?php

namespace Database\Seeders;

use App\Models\CriterioRsc;
use App\Models\Escolaridade;
use App\Models\NivelRsc;
use App\Models\RequisitoRsc;
use Illuminate\Database\Seeder;

class RscSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $escolaridades = collect([
            ['nome' => 'Ensino Fundamental Incompleto', 'ordem' => 1],
            ['nome' => 'Ensino Fundamental', 'ordem' => 2],
            ['nome' => 'Ensino Médio/Técnico', 'ordem' => 3],
            ['nome' => 'Graduação', 'ordem' => 4],
            ['nome' => 'Especialização', 'ordem' => 5],
            ['nome' => 'Mestrado', 'ordem' => 6],
            ['nome' => 'Doutorado', 'ordem' => 7],
        ])->mapWithKeys(fn (array $data): array => [
            $data['ordem'] => Escolaridade::query()->updateOrCreate(['ordem' => $data['ordem']], $data),
        ]);

        $niveis = collect([
            ['codigo' => 'RSC-I', 'nome' => 'RSC-PCCTAE I', 'escolaridade_minima_id' => $escolaridades[1]->id, 'pontos_minimos' => 10, 'criterios_minimos' => 0, 'percentual_iq' => 10],
            ['codigo' => 'RSC-II', 'nome' => 'RSC-PCCTAE II', 'escolaridade_minima_id' => $escolaridades[2]->id, 'pontos_minimos' => 15, 'criterios_minimos' => 2, 'percentual_iq' => 15],
            ['codigo' => 'RSC-III', 'nome' => 'RSC-PCCTAE III', 'escolaridade_minima_id' => $escolaridades[3]->id, 'pontos_minimos' => 25, 'criterios_minimos' => 2, 'percentual_iq' => 25],
            ['codigo' => 'RSC-IV', 'nome' => 'RSC-PCCTAE IV', 'escolaridade_minima_id' => $escolaridades[4]->id, 'pontos_minimos' => 30, 'criterios_minimos' => 3, 'percentual_iq' => 30],
            ['codigo' => 'RSC-V', 'nome' => 'RSC-PCCTAE V', 'escolaridade_minima_id' => $escolaridades[5]->id, 'pontos_minimos' => 52, 'criterios_minimos' => 5, 'percentual_iq' => 52],
            ['codigo' => 'RSC-VI', 'nome' => 'RSC-PCCTAE VI', 'escolaridade_minima_id' => $escolaridades[6]->id, 'pontos_minimos' => 75, 'criterios_minimos' => 7, 'percentual_iq' => 75],
        ])->mapWithKeys(fn (array $data): array => [
            $data['codigo'] => NivelRsc::query()->updateOrCreate(['codigo' => $data['codigo']], [...$data, 'ativo' => true]),
        ]);

        $requisitos = collect([
            1 => ['nome' => 'Grupos de trabalho e comissões', 'descricao' => 'Participação em grupos de trabalho, comissões, comitês, núcleos, representações ou similares, formalmente instituídos ou reconhecidos pelo órgão ou pela entidade.'],
            2 => ['nome' => 'Projetos institucionais', 'descricao' => 'Participação e atuação em projetos institucionais, na gestão, no apoio ao ensino, à pesquisa, à extensão, de inovação e assistência especializada.'],
            3 => ['nome' => 'Premiações públicas', 'descricao' => 'Recebimento de premiação em evento de reconhecimento público por projetos implementados na administração pública.'],
            4 => ['nome' => 'Responsabilidades especializadas', 'descricao' => 'Designação para assunção de responsabilidades técnico-administrativas ou especializadas.'],
            5 => ['nome' => 'Direção e assessoramento', 'descricao' => 'Exercício de função ou cargo de direção ou de assessoramento institucional.'],
            6 => ['nome' => 'Conhecimento científico ou técnico', 'descricao' => 'Produção, prospecção e difusão de conhecimento científico ou técnico.'],
        ])->mapWithKeys(fn (array $data, int $numero): array => [
            $numero => RequisitoRsc::query()->updateOrCreate(['numero' => $numero], ['numero' => $numero, ...$data]),
        ]);

        $niveis['RSC-IV']->requisitosObrigatorios()->syncWithPivotValues([
            $requisitos[2]->id,
            $requisitos[4]->id,
            $requisitos[5]->id,
            $requisitos[6]->id,
        ], ['tipo_regra' => 'AO_MENOS_UM']);

        $niveis['RSC-V']->requisitosObrigatorios()->syncWithPivotValues([
            $requisitos[4]->id,
            $requisitos[5]->id,
            $requisitos[6]->id,
        ], ['tipo_regra' => 'AO_MENOS_UM']);

        $niveis['RSC-VI']->requisitosObrigatorios()->syncWithPivotValues([
            $requisitos[6]->id,
        ], ['tipo_regra' => 'AO_MENOS_UM']);

        foreach ($this->criterios() as $numeroRequisito => $criterios) {
            foreach ($criterios as $item => $data) {
                $variacoes = $data['variacoes'] ?? [];
                unset($data['variacoes']);

                $criterio = CriterioRsc::query()->updateOrCreate(
                    ['requisito_rsc_id' => $requisitos[$numeroRequisito]->id, 'item' => $item],
                    ['requisito_rsc_id' => $requisitos[$numeroRequisito]->id, 'item' => $item, 'ativo' => true, 'permite_multiplicacao' => true, ...$data],
                );

                foreach ($variacoes as $nome => $pontos) {
                    $criterio->variacoesPontuacao()->updateOrCreate(['nome' => $nome], ['pontos' => $pontos]);
                }
            }
        }
    }

    /**
     * @return array<int, array<int, array{descricao: string, unidade_medida: string, pontos: float|int, variacoes?: array<string, float|int>}>>
     */
    private function criterios(): array
    {
        return [
            1 => [
                1 => ['descricao' => 'Exercício do mandato como membro de conselhos superiores e conselhos de unidades e órgãos colegiados da Instituição Federal de Ensino', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 3],
                2 => ['descricao' => 'Coordenação ou presidência de núcleos, representações, grupos de trabalho ou similares, comissões ou comitês previstos no âmbito da administração pública, regularmente instituídos, ou reconhecidos pelo órgão ou pela entidade', 'unidade_medida' => 'Por designação', 'pontos' => 4.5],
                3 => ['descricao' => 'Participação como membro de núcleos, representações, grupos de trabalho ou similares, comissões ou comitês previstos no âmbito da administração pública, regularmente instituídos', 'unidade_medida' => 'Por designação', 'pontos' => 3],
                4 => ['descricao' => 'Participação como defensor dativo ou como membro de equipe designada em processos de apuração de materialidade e responsabilidade, como sindicância, processo administrativo disciplinar e tomada de contas especial', 'unidade_medida' => 'Por designação', 'pontos' => 3],
                5 => ['descricao' => 'Atuação em atividades de organização, fiscalização, execução de exame de seleção, vestibular ou concursos', 'unidade_medida' => 'Por designação', 'pontos' => 4.5],
                6 => ['descricao' => 'Atuação em atividades de elaboração, revisão e/ou correção de provas de exame de seleção, vestibular ou concursos', 'unidade_medida' => 'Por designação', 'pontos' => 3],
                7 => ['descricao' => 'Exercício de mandato em entidade sindical da categoria', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 1.5],
                8 => ['descricao' => 'Participação como membro em programas ou projetos de políticas públicas externas à Instituição Federal de Ensino, desde que comprovada a obtenção de resultados institucionais relevantes', 'unidade_medida' => 'Por designação', 'pontos' => 3],
                9 => ['descricao' => 'Representação legal da Instituição Federal de Ensino junto a órgãos e entidades do Poder Público ou responsabilidade técnica junto a órgãos de fiscalização, controle e regulação', 'unidade_medida' => 'Por designação', 'pontos' => 7.5],
                10 => ['descricao' => 'Atuação técnica externa, formalmente autorizada ou reconhecida pela Instituição Federal de Ensino de lotação, em órgãos estatais ou paraestatais, escolas de governo, agências reguladoras ou organismos internacionais, com contribuição ou repercussão institucional', 'unidade_medida' => 'Por produto', 'pontos' => 4.5],
            ],
            2 => [
                1 => ['descricao' => 'Coordenação de projetos institucionais (ensino, pesquisa, extensão, gestão e inovação)', 'unidade_medida' => 'Por projeto', 'pontos' => 7.5],
                2 => ['descricao' => 'Participação em atividades técnicas e/ou especializadas em projetos, incluída a elaboração de projetos pedagógicos, programas e/ou ações institucionais (ensino, pesquisa, extensão, gestão e inovação)', 'unidade_medida' => 'Por projeto', 'pontos' => 4.5],
                3 => ['descricao' => 'Participação em comissão ou conselho editorial de livros, revistas ou publicações científicas ou outras publicações acadêmicas', 'unidade_medida' => 'Por mandato', 'pontos' => 7.5],
                4 => ['descricao' => 'Participação em atividade de Cooperação Técnica Interinstitucional em projetos institucionais', 'unidade_medida' => 'Por projeto', 'pontos' => 3],
                5 => ['descricao' => 'Participação em atividades de orientação, tutoria, preceptoria ou supervisão', 'unidade_medida' => 'Por designação', 'pontos' => 3],
                6 => ['descricao' => 'Participação em atividades de produção ou reformulação de material acessível, ou técnico de referência (manuais, roteiros técnicos)', 'unidade_medida' => 'Por produto', 'pontos' => 3],
                7 => ['descricao' => 'Participação em atividade de avaliação de trabalho ou atuação como jurado em eventos acadêmicos, científicos, culturais, esportivos e técnicos', 'unidade_medida' => 'Por evento', 'pontos' => 3],
                8 => ['descricao' => 'Participação em atividade institucional de produção audiovisual, artística, exposição, podcast ou outras formas de apresentação', 'unidade_medida' => 'Por projeto', 'pontos' => 3],
                9 => ['descricao' => 'Participação em programas de formação continuada e/ou ações de desenvolvimento de competências, desde que não utilizada para fins de aceleração da promoção na carreira (carga horária mínima de dez horas)', 'unidade_medida' => 'Por capacitação', 'pontos' => 1],
                10 => ['descricao' => 'Desempenho de atividade técnica especializada, formalmente reconhecida pela Instituição Federal de Ensino, com demonstração de domínio técnico diferenciado e contribuição institucional relevante na área de atuação', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 1],
                11 => ['descricao' => 'Participação em capacitação, fórum, oficina, workshop e congresso, com carga horária mínima de dez horas, vinculada aos interesses da Instituição Federal de Ensino', 'unidade_medida' => 'Por evento', 'pontos' => 1],
            ],
            3 => [
                1 => ['descricao' => 'Recebimento de premiação de âmbito internacional por projeto implementado na administração pública', 'unidade_medida' => 'Por prêmio', 'pontos' => 20],
                2 => ['descricao' => 'Recebimento de premiação de âmbito nacional por projeto implementado na administração pública', 'unidade_medida' => 'Por prêmio', 'pontos' => 15],
                3 => ['descricao' => 'Recebimento de premiação de âmbito local ou institucional, formalmente instituído, por projeto implementado na administração pública', 'unidade_medida' => 'Por prêmio', 'pontos' => 7.5],
            ],
            4 => [
                1 => ['descricao' => 'Atuação tecnicamente qualificada na operação, na implantação, no suporte ou no apoio a desenvolvimento, parametrização ou aperfeiçoamento de sistemas estruturantes da administração pública', 'unidade_medida' => 'Por sistema', 'pontos' => 4.5],
                2 => ['descricao' => 'Elaboração de projeto básico ou de termo de referência, ou participação como membro de equipe de planejamento de contratação', 'unidade_medida' => 'Por designação', 'pontos' => 3],
                3 => ['descricao' => 'Exercício de atividades de gestão ou fiscalização de contratos de aquisição, serviços, convênios e acordos ou instrumentos correlatos', 'unidade_medida' => 'Por designação', 'pontos' => 4.5],
                4 => ['descricao' => 'Exercício de atividades relacionadas à licitação e às suas excepcionalidades', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 3],
                5 => ['descricao' => 'Participação em atividades de apoio técnico especializado em políticas, programas e ações de promoção na área de saúde humana, animal e ambiente, de acessibilidade ou diversidade, de interesse institucional', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 3],
                6 => ['descricao' => 'Atuação tecnicamente qualificada em ambientes ou processos que demandem condições especiais de segurança, cuidado ou conformidade com requisitos legais e regulatórios, desde que não receba adicional de periculosidade ou insalubridade em razão das mesmas condições', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 3],
                7 => ['descricao' => 'Atuação em sistemas e/ou processos de trabalho institucionais em ensino, pesquisa, extensão, gestão e inovação, desde que não constitua atividade habitual do cargo', 'unidade_medida' => 'Por designação', 'pontos' => 3],
                8 => ['descricao' => 'Atuação como responsável por setor ou por unidade, formalmente designado, desde que a designação não gere pagamento de remuneração', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 4.5],
            ],
            5 => [
                1 => ['descricao' => 'Exercício de cargo de direção (CD-02) ou equivalente', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 9, 'variacoes' => ['Titular' => 9, 'Substituto' => 4.5]],
                2 => ['descricao' => 'Exercício de cargo de direção (CD-03 e 04) ou equivalente', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 7.5, 'variacoes' => ['Titular' => 7.5, 'Substituto' => 3]],
                3 => ['descricao' => 'Exercício de função gratificada (FG-01 e 02) ou equivalente', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 4.5, 'variacoes' => ['Titular' => 4.5, 'Substituto' => 1.5]],
                4 => ['descricao' => 'Exercício de função gratificada (a partir da FG-03) ou equivalente', 'unidade_medida' => 'Por ano ou fração acima de seis meses', 'pontos' => 3, 'variacoes' => ['Titular' => 3, 'Substituto' => 1]],
            ],
            6 => [
                1 => ['descricao' => 'Carta patente relacionada aos interesses institucionais', 'unidade_medida' => 'Por patente', 'pontos' => 30],
                2 => ['descricao' => 'Participação relevante no desenvolvimento de protótipos, depósitos e/ou registros de propriedade intelectual ou privilégio de invenção relacionada aos interesses institucionais', 'unidade_medida' => 'Por projeto', 'pontos' => 25],
                3 => ['descricao' => 'Participação em transferência de tecnologia, licenciamento ou exploração de ativo tecnológico, como autor ou inventor relacionada aos interesses institucionais', 'unidade_medida' => 'Por produto', 'pontos' => 20],
                4 => ['descricao' => 'Conclusão de curso de educação formal superior ao exigido para o ingresso no cargo de que é titular e que não seja utilizado para percepção de Incentivo à Qualificação - IQ', 'unidade_medida' => 'Por curso', 'pontos' => 15],
                5 => ['descricao' => 'Participação relevante na implantação ou desenvolvimento de produto, projeto, processo, técnica ou tecnologia de interesse institucional', 'unidade_medida' => 'Por produto', 'pontos' => 15],
                6 => ['descricao' => 'Atuação em atividade de liderança ou vice-liderança de grupo de pesquisa ou extensão registrado em órgão ou sistema oficial de reconhecimento institucional', 'unidade_medida' => 'Por grupo de pesquisa', 'pontos' => 7.5],
                7 => ['descricao' => 'Participação como membro em grupo de pesquisa devidamente registrado em órgão ou sistema oficial de reconhecimento institucional', 'unidade_medida' => 'Por projeto', 'pontos' => 3],
                8 => ['descricao' => 'Aprovação de projeto para a captação de recursos para a Instituição Federal de Ensino', 'unidade_medida' => 'Por projeto', 'pontos' => 7.5],
                9 => ['descricao' => 'Publicação ou organização de livro relacionado aos interesses institucionais (com ISBN e Conselho Editorial)', 'unidade_medida' => 'Por produto', 'pontos' => 20],
                10 => ['descricao' => 'Autoria ou coautoria de capítulo de livro, de artigo publicado em revista especializada, jornal científico ou periódico, relacionado aos interesses institucionais', 'unidade_medida' => 'Por publicação', 'pontos' => 7.5],
                11 => ['descricao' => 'Apresentação de trabalho de interesse institucional em congresso, seminário ou outros eventos', 'unidade_medida' => 'Por produto', 'pontos' => 4.5],
                12 => ['descricao' => 'Produção de material técnico, científico, metodológico ou administrativo estruturado que visa à difusão do conhecimento', 'unidade_medida' => 'Por produto', 'pontos' => 4.5],
                13 => ['descricao' => 'Avaliação do projeto de ensino e/ou pesquisa e/ou extensão e/ou inovação', 'unidade_medida' => 'Por projeto', 'pontos' => 4.5],
                14 => ['descricao' => 'Participação em atividade de difusão ou apoio à formação institucional (expositor, facilitador, colaborador)', 'unidade_medida' => 'Por evento', 'pontos' => 3],
                15 => ['descricao' => 'Atuação formalmente autorizada como instrutor, tutor, palestrante, autor de conteúdo técnico ou orientador em ação formativa estruturada de interesse institucional, prevista em plano ou programa de desenvolvimento de pessoas', 'unidade_medida' => 'Por curso', 'pontos' => 4.5],
                16 => ['descricao' => 'Atuação na coordenação de congresso, simpósio ou seminário de interesse institucional', 'unidade_medida' => 'Por evento', 'pontos' => 3.5],
                17 => ['descricao' => 'Exercício de atividade de coorientação de trabalho de conclusão de curso em diferentes modalidades de ensino', 'unidade_medida' => 'Por evento', 'pontos' => 4.5],
                18 => ['descricao' => 'Autoria de obra artística ou cultural registrada com contribuição ou repercussão institucional comprovada', 'unidade_medida' => 'Por produto', 'pontos' => 3],
                19 => ['descricao' => 'Atuação institucional no enfrentamento de situações de surto, epidemia e pandemia', 'unidade_medida' => 'Por mês', 'pontos' => 1],
            ],
        ];
    }
}
