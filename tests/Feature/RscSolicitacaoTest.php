<?php

use App\Models\CriterioRsc;
use App\Models\Escolaridade;
use App\Models\NivelRsc;
use App\Models\Servidor;
use App\Models\SolicitacaoRsc;
use App\Models\SolicitacaoRscCriterio;
use App\Models\User;
use App\Rsc\SolicitacaoRscStatus;
use Database\Seeders\RscSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated user can save servidor profile', function () {
    $this->seed(RscSeeder::class);

    $user = User::factory()->create();
    $escolaridade = Escolaridade::query()->where('nome', 'Ensino Médio/Técnico')->firstOrFail();

    $this->actingAs($user)
        ->put(route('rsc.profile.update'), [
            'nome' => 'Rafael Servidor',
            'siape' => '1234567',
            'cpf' => '123.456.789-00',
            'email_institucional' => 'rafael@example.edu.br',
            'cargo' => 'Técnico Administrativo',
            'unidade_lotacao' => 'PROGEP',
            'data_ingresso_cargo' => '2020-01-10',
            'estagio_probatorio' => false,
            'escolaridade_id' => $escolaridade->id,
        ])
        ->assertRedirect(route('rsc.solicitacoes.index'));

    expect($user->servidor()->exists())->toBeTrue();
});

test('create page displays every rsc requirement and criterion', function () {
    $this->seed(RscSeeder::class);

    $user = User::factory()->create();
    servidorFor($user, 'Mestrado');

    $this->actingAs($user)
        ->get(route('rsc.solicitacoes.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rsc/solicitacoes/Create')
            ->has('requisitos', 6)
            ->where('requisitos.0.numero', 1)
            ->has('requisitos.0.criterios', 10)
            ->where('requisitos.5.numero', 6)
            ->has('requisitos.5.criterios', 19)
        );
});

test('submission is blocked when automatic rules are not met', function () {
    $this->seed(RscSeeder::class);

    $user = User::factory()->create();
    $servidor = servidorFor($user, 'Mestrado');
    $nivel = NivelRsc::query()->where('codigo', 'RSC-VI')->firstOrFail();
    $criterio = CriterioRsc::query()->whereRelation('requisito', 'numero', 1)->firstOrFail();

    $this->actingAs($user)
        ->from(route('rsc.solicitacoes.create'))
        ->post(route('rsc.solicitacoes.store'), [
            'nivel_rsc_id' => $nivel->id,
            'intent' => 'submit',
            'saldo_pontos_anterior' => 0,
            'memorial' => 'Memorial profissional.',
            'declaracao_veracidade' => true,
            'declaracao_nao_reutilizacao' => true,
            'atividades' => [
                [
                    'criterio_rsc_id' => $criterio->id,
                    'titulo_atividade' => 'Comissão interna',
                    'descricao_atividade' => 'Participação em comissão.',
                    'quantidade' => 1,
                    'atividade_exercicio_cargo' => true,
                    'atividade_ordinaria_cargo' => false,
                    'justificativa_relevancia' => 'Contribuição institucional relevante.',
                    'usado_em_concessao_anterior' => false,
                    'tipo_documento' => 'Portaria',
                ],
            ],
        ])
        ->assertRedirect(route('rsc.solicitacoes.create'))
        ->assertSessionHasErrors('solicitacao');

    expect($servidor->solicitacoes()->count())->toBe(0);
});

test('valid request can be submitted with calculated score', function () {
    $this->seed(RscSeeder::class);
    Storage::fake('local');

    $user = User::factory()->create();
    servidorFor($user, 'Ensino Médio/Técnico');
    $nivel = NivelRsc::query()->where('codigo', 'RSC-III')->firstOrFail();
    $criterios = CriterioRsc::query()->whereRelation('requisito', 'numero', 3)->orderBy('item')->take(2)->get();

    $this->actingAs($user)
        ->post(route('rsc.solicitacoes.store'), [
            'nivel_rsc_id' => $nivel->id,
            'intent' => 'submit',
            'saldo_pontos_anterior' => 0,
            'memorial' => 'Trajetória profissional com atuação institucional relevante.',
            'declaracao_veracidade' => true,
            'declaracao_nao_reutilizacao' => true,
            'atividades' => [
                validAtividade($criterios[0]->id, 1, 'premio-internacional.pdf'),
                validAtividade($criterios[1]->id, 1, 'premio-nacional.pdf'),
            ],
        ])
        ->assertRedirect();

    $solicitacao = SolicitacaoRsc::query()->firstOrFail();

    expect($solicitacao->status)->toBe(SolicitacaoRscStatus::Submetida)
        ->and((float) $solicitacao->pontos_declarados)->toBe(35.0)
        ->and($solicitacao->criterios_declarados)->toBe(2)
        ->and($solicitacao->criterios()->count())->toBe(2);
});

test('draft request can be edited and submitted later', function () {
    $this->seed(RscSeeder::class);
    Storage::fake('local');

    $user = User::factory()->create();
    servidorFor($user, 'Mestrado');
    $nivel = NivelRsc::query()->where('codigo', 'RSC-VI')->firstOrFail();
    $criterio = CriterioRsc::query()->whereRelation('requisito', 'numero', 1)->firstOrFail();

    $this->actingAs($user)
        ->post(route('rsc.solicitacoes.store'), [
            'nivel_rsc_id' => $nivel->id,
            'intent' => 'draft',
            'saldo_pontos_anterior' => 0,
            'memorial' => 'Memorial em elaboração.',
            'declaracao_veracidade' => true,
            'declaracao_nao_reutilizacao' => true,
            'atividades' => [
                validAtividade($criterio->id, 1, 'comissao.pdf'),
            ],
        ])
        ->assertRedirect();

    $solicitacao = SolicitacaoRsc::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('rsc.solicitacoes.edit', $solicitacao))
        ->assertSuccessful();

    $criterios = CriterioRsc::query()->whereRelation('requisito', 'numero', 6)->orderByDesc('pontos')->take(7)->get();

    $this->actingAs($user)
        ->put(route('rsc.solicitacoes.update', $solicitacao), [
            'nivel_rsc_id' => $nivel->id,
            'intent' => 'submit',
            'saldo_pontos_anterior' => 0,
            'memorial' => 'Memorial profissional concluído.',
            'declaracao_veracidade' => true,
            'declaracao_nao_reutilizacao' => true,
            'atividades' => $criterios
                ->values()
                ->map(fn (CriterioRsc $criterio, int $index): array => validAtividade($criterio->id, 1, "requisito-vi-{$index}.pdf"))
                ->all(),
        ])
        ->assertRedirect(route('rsc.solicitacoes.show', $solicitacao));

    $solicitacao->refresh();

    expect($solicitacao->status)->toBe(SolicitacaoRscStatus::Submetida)
        ->and((float) $solicitacao->pontos_declarados)->toBeGreaterThanOrEqual(75.0)
        ->and($solicitacao->criterios()->count())->toBe(7);
});

test('draft keeps existing documents when submitted from edit form', function () {
    $this->seed(RscSeeder::class);
    Storage::fake('local');

    $user = User::factory()->create();
    servidorFor($user, 'Ensino Médio/Técnico');
    $nivel = NivelRsc::query()->where('codigo', 'RSC-III')->firstOrFail();
    $criterios = CriterioRsc::query()->whereRelation('requisito', 'numero', 3)->orderBy('item')->take(2)->get();

    $this->actingAs($user)
        ->post(route('rsc.solicitacoes.store'), [
            'nivel_rsc_id' => $nivel->id,
            'intent' => 'draft',
            'saldo_pontos_anterior' => 0,
            'memorial' => 'Trajetória profissional com atuação institucional relevante.',
            'declaracao_veracidade' => true,
            'declaracao_nao_reutilizacao' => true,
            'atividades' => [
                validAtividade($criterios[0]->id, 1, 'premio-internacional.pdf'),
                validAtividade($criterios[1]->id, 1, 'premio-nacional.pdf'),
            ],
        ])
        ->assertRedirect();

    $solicitacao = SolicitacaoRsc::query()->with('criterios.documentos')->firstOrFail();
    $atividades = $solicitacao->criterios->map(fn ($item): array => atividadeSemNovoDocumento($item))->all();

    $this->actingAs($user)
        ->put(route('rsc.solicitacoes.update', $solicitacao), [
            'nivel_rsc_id' => $nivel->id,
            'intent' => 'submit',
            'saldo_pontos_anterior' => 0,
            'memorial' => 'Trajetória profissional com atuação institucional relevante.',
            'declaracao_veracidade' => true,
            'declaracao_nao_reutilizacao' => true,
            'atividades' => $atividades,
        ])
        ->assertRedirect(route('rsc.solicitacoes.show', $solicitacao));

    $solicitacao->refresh();

    expect($solicitacao->status)->toBe(SolicitacaoRscStatus::Submetida)
        ->and($solicitacao->criterios()->count())->toBe(2)
        ->and($solicitacao->criterios()->withCount('documentos')->get()->sum('documentos_count'))->toBe(2);
});

test('submitted request cannot be edited', function () {
    $this->seed(RscSeeder::class);
    Storage::fake('local');

    $user = User::factory()->create();
    servidorFor($user, 'Ensino Médio/Técnico');
    $nivel = NivelRsc::query()->where('codigo', 'RSC-III')->firstOrFail();
    $criterios = CriterioRsc::query()->whereRelation('requisito', 'numero', 3)->orderBy('item')->take(2)->get();

    $this->actingAs($user)
        ->post(route('rsc.solicitacoes.store'), [
            'nivel_rsc_id' => $nivel->id,
            'intent' => 'submit',
            'saldo_pontos_anterior' => 0,
            'memorial' => 'Trajetória profissional com atuação institucional relevante.',
            'declaracao_veracidade' => true,
            'declaracao_nao_reutilizacao' => true,
            'atividades' => [
                validAtividade($criterios[0]->id, 1, 'premio-internacional.pdf'),
                validAtividade($criterios[1]->id, 1, 'premio-nacional.pdf'),
            ],
        ])
        ->assertRedirect();

    $solicitacao = SolicitacaoRsc::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('rsc.solicitacoes.edit', $solicitacao))
        ->assertForbidden();
});

function servidorFor(User $user, string $escolaridadeNome): Servidor
{
    $escolaridade = Escolaridade::query()->where('nome', $escolaridadeNome)->firstOrFail();

    return Servidor::query()->create([
        'user_id' => $user->id,
        'escolaridade_id' => $escolaridade->id,
        'nome' => $user->name,
        'siape' => fake()->unique()->numerify('#######'),
        'cpf' => fake()->unique()->numerify('###.###.###-##'),
        'email_institucional' => $user->email,
        'cargo' => 'Técnico Administrativo',
        'unidade_lotacao' => 'PROGEP',
        'data_ingresso_cargo' => '2020-01-10',
        'estagio_probatorio' => false,
        'ativo' => true,
    ]);
}

/**
 * @return array<string, mixed>
 */
function validAtividade(int $criterioId, int $quantidade, string $filename): array
{
    return [
        'criterio_rsc_id' => $criterioId,
        'titulo_atividade' => 'Premiação institucional',
        'descricao_atividade' => 'Projeto reconhecido por seus resultados.',
        'quantidade' => $quantidade,
        'atividade_exercicio_cargo' => true,
        'atividade_ordinaria_cargo' => false,
        'justificativa_relevancia' => 'A atividade demonstrou inovação e resultado institucional relevante.',
        'usado_em_concessao_anterior' => false,
        'tipo_documento' => 'Comprovante de premiação',
        'documentos' => [
            UploadedFile::fake()->create($filename, 100, 'application/pdf'),
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function atividadeSemNovoDocumento(SolicitacaoRscCriterio $item): array
{
    return [
        'id' => $item->id,
        'criterio_rsc_id' => $item->criterio_rsc_id,
        'variacao_pontuacao_id' => $item->variacao_pontuacao_id,
        'titulo_atividade' => $item->titulo_atividade,
        'descricao_atividade' => $item->descricao_atividade,
        'data_inicio' => $item->data_inicio?->toDateString(),
        'data_fim' => $item->data_fim?->toDateString(),
        'quantidade' => $item->quantidade,
        'atividade_exercicio_cargo' => $item->atividade_exercicio_cargo,
        'atividade_ordinaria_cargo' => $item->atividade_ordinaria_cargo,
        'justificativa_relevancia' => $item->justificativa_relevancia,
        'usado_em_concessao_anterior' => $item->usado_em_concessao_anterior,
        'tipo_documento' => $item->documentos->first()?->tipo_documento ?? 'Comprovante',
        'observacao_documento' => $item->documentos->first()?->observacao,
        'documentos_existentes_count' => $item->documentos->count(),
    ];
}
