<?php

use App\Models\CriterioRsc;
use App\Models\Escolaridade;
use App\Models\NivelRsc;
use App\Models\Servidor;
use App\Models\SolicitacaoRsc;
use App\Models\User;
use App\Rsc\SolicitacaoRscStatus;
use Database\Seeders\RscSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
