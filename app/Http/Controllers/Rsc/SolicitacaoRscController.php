<?php

namespace App\Http\Controllers\Rsc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rsc\StoreSolicitacaoRscRequest;
use App\Models\CriterioRsc;
use App\Models\HistoricoSolicitacaoRsc;
use App\Models\NivelRsc;
use App\Models\RequisitoRsc;
use App\Models\Servidor;
use App\Models\SolicitacaoRsc;
use App\Models\SolicitacaoRscCriterio;
use App\Models\User;
use App\Rsc\CalculaSolicitacaoRsc;
use App\Rsc\SolicitacaoRscStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SolicitacaoRscController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $servidor = $this->servidorAutenticado();

        if (! $servidor) {
            return to_route('rsc.profile.edit')->with('warning', 'Preencha o perfil funcional antes de abrir uma solicitação.');
        }

        return Inertia::render('rsc/solicitacoes/Index', [
            'servidor' => $servidor->load('escolaridade'),
            'solicitacoes' => $servidor->solicitacoes()
                ->with('nivelRsc')
                ->latest('created_at')
                ->get()
                ->map(fn (SolicitacaoRsc $solicitacao): array => $this->serializeSolicitacaoResumo($solicitacao)),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $servidor = $this->servidorAutenticado()?->load('escolaridade');

        if (! $servidor) {
            return to_route('rsc.profile.edit')->with('warning', 'Preencha o perfil funcional antes de abrir uma solicitação.');
        }

        return Inertia::render('rsc/solicitacoes/Create', [
            'servidor' => $servidor,
            'niveis' => NivelRsc::query()
                ->with(['escolaridadeMinima', 'requisitosObrigatorios'])
                ->where('ativo', true)
                ->orderBy('id')
                ->get(),
            'requisitos' => RequisitoRsc::query()
                ->with(['criterios.variacoesPontuacao'])
                ->orderBy('numero')
                ->get(),
        ]);
    }

    public function store(StoreSolicitacaoRscRequest $request, CalculaSolicitacaoRsc $calcula): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $servidor = Servidor::query()
            ->with(['escolaridade', 'ultimaConcessao'])
            ->where('user_id', $user->id)
            ->firstOrFail();
        $nivel = NivelRsc::query()->with(['escolaridadeMinima', 'requisitosObrigatorios'])->findOrFail($request->integer('nivel_rsc_id'));
        $validated = $request->validated();
        $resultado = $calcula->calcular(
            nivel: $nivel,
            servidor: $servidor,
            atividades: $validated['atividades'],
            memorial: $validated['memorial'] ?? null,
            declaracaoVeracidade: (bool) $validated['declaracao_veracidade'],
            declaracaoNaoReutilizacao: (bool) $validated['declaracao_nao_reutilizacao'],
        );

        if ($validated['intent'] === 'submit' && ! $resultado['apta']) {
            return back()
                ->withErrors(['solicitacao' => implode(' ', $resultado['bloqueios'])])
                ->withInput();
        }

        $solicitacao = DB::transaction(function () use ($request, $servidor, $nivel, $validated, $resultado, $user): SolicitacaoRsc {
            $status = match (true) {
                $validated['intent'] === 'submit' => SolicitacaoRscStatus::Submetida,
                $resultado['apta'] => SolicitacaoRscStatus::AptaParaSubmissao,
                default => SolicitacaoRscStatus::Rascunho,
            };

            $solicitacao = SolicitacaoRsc::query()->create([
                'servidor_id' => $servidor->id,
                'nivel_rsc_id' => $nivel->id,
                'numero_protocolo' => $this->gerarProtocolo(),
                'status' => $status,
                'data_abertura' => now(),
                'data_submissao' => $validated['intent'] === 'submit' ? now() : null,
                'saldo_pontos_anterior' => $validated['saldo_pontos_anterior'] ?? 0,
                'pontos_declarados' => $resultado['pontos'],
                'criterios_declarados' => $resultado['criterios'],
                'memorial' => $validated['memorial'] ?? null,
                'declaracao_veracidade' => $validated['declaracao_veracidade'],
                'declaracao_nao_reutilizacao' => $validated['declaracao_nao_reutilizacao'],
            ]);

            foreach ($resultado['itens'] as $index => $item) {
                $criterio = SolicitacaoRscCriterio::query()->create([
                    'solicitacao_rsc_id' => $solicitacao->id,
                    'criterio_rsc_id' => $item['criterio_rsc_id'],
                    'variacao_pontuacao_id' => $item['variacao_pontuacao_id'],
                    'titulo_atividade' => $item['titulo_atividade'],
                    'descricao_atividade' => $item['descricao_atividade'],
                    'data_inicio' => $item['data_inicio'] ?? null,
                    'data_fim' => $item['data_fim'] ?? null,
                    'quantidade' => $item['quantidade'],
                    'pontos_unitarios' => $item['pontos_unitarios'],
                    'pontos_calculados' => $item['pontos_calculados'],
                    'atividade_exercicio_cargo' => $item['atividade_exercicio_cargo'],
                    'atividade_ordinaria_cargo' => $item['atividade_ordinaria_cargo'],
                    'justificativa_relevancia' => $item['justificativa_relevancia'],
                    'usado_em_concessao_anterior' => $item['usado_em_concessao_anterior'],
                ]);

                $documentos = $request->file("atividades.{$index}.documentos", []);

                if ($documentos instanceof UploadedFile) {
                    $documentos = [$documentos];
                }

                foreach ($documentos as $documento) {
                    $criterio->documentos()->create([
                        'tipo_documento' => $item['tipo_documento'],
                        'nome_original' => $documento->getClientOriginalName(),
                        'caminho_arquivo' => $documento->store("rsc/solicitacoes/{$solicitacao->id}"),
                        'mime_type' => (string) $documento->getMimeType(),
                        'tamanho' => $documento->getSize(),
                        'observacao' => $item['observacao_documento'] ?? null,
                    ]);
                }
            }

            HistoricoSolicitacaoRsc::query()->create([
                'solicitacao_rsc_id' => $solicitacao->id,
                'usuario_id' => $user->id,
                'status_anterior' => null,
                'status_novo' => $status->value,
                'descricao' => $status === SolicitacaoRscStatus::Submetida
                    ? 'Solicitação submetida pelo servidor.'
                    : 'Solicitação salva pelo servidor.',
            ]);

            return $solicitacao;
        });

        return to_route('rsc.solicitacoes.show', $solicitacao)->with('success', 'Solicitação registrada.');
    }

    public function show(SolicitacaoRsc $solicitacao): Response
    {
        abort_unless($solicitacao->servidor()->where('user_id', auth()->id())->exists(), 403);

        $solicitacao->load([
            'servidor.escolaridade',
            'nivelRsc.escolaridadeMinima',
            'historicos.usuario',
        ]);
        $criterios = $solicitacao->criterios()
            ->with(['criterio.requisito', 'variacaoPontuacao', 'documentos'])
            ->get();

        return Inertia::render('rsc/solicitacoes/Show', [
            'solicitacao' => [
                ...$this->serializeSolicitacaoResumo($solicitacao),
                'servidor' => $solicitacao->servidor,
                'memorial' => $solicitacao->memorial,
                'criterios' => $criterios->map(fn (SolicitacaoRscCriterio $item): array => [
                    'id' => $item->id,
                    'titulo_atividade' => $item->titulo_atividade,
                    'descricao_atividade' => $item->descricao_atividade,
                    'quantidade' => $item->quantidade,
                    'pontos_unitarios' => $item->pontos_unitarios,
                    'pontos_calculados' => $item->pontos_calculados,
                    'justificativa_relevancia' => $item->justificativa_relevancia,
                    'criterio' => $item->criterio,
                    'variacao' => $item->variacaoPontuacao,
                    'documentos_count' => $item->documentos->count(),
                ]),
                'historicos' => $solicitacao->historicos,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSolicitacaoResumo(SolicitacaoRsc $solicitacao): array
    {
        $status = SolicitacaoRscStatus::from((string) $solicitacao->getRawOriginal('status'));
        $dataAbertura = $solicitacao->getAttribute('data_abertura');
        $dataSubmissao = $solicitacao->getAttribute('data_submissao');

        return [
            'id' => $solicitacao->id,
            'numero_protocolo' => $solicitacao->numero_protocolo,
            'status' => $status->value,
            'status_label' => $status->label(),
            'nivel' => $solicitacao->nivelRsc,
            'pontos_declarados' => $solicitacao->pontos_declarados,
            'criterios_declarados' => $solicitacao->criterios_declarados,
            'data_abertura' => $dataAbertura ? Carbon::parse($dataAbertura)->toDateTimeString() : null,
            'data_submissao' => $dataSubmissao ? Carbon::parse($dataSubmissao)->toDateTimeString() : null,
        ];
    }

    private function servidorAutenticado(): ?Servidor
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        return Servidor::query()->where('user_id', $userId)->first();
    }

    private function gerarProtocolo(): string
    {
        do {
            $protocolo = 'RSC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (SolicitacaoRsc::query()->where('numero_protocolo', $protocolo)->exists());

        return $protocolo;
    }
}
