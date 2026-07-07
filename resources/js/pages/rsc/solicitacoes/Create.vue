<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CheckCircle2, CircleAlert, FileText, ListChecks, Paperclip, Plus, Search, Target, Trash2, Upload, X } from '@lucide/vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/rsc/solicitacoes';

type Variacao = {
    id: number;
    nome: string;
    pontos: string;
};

type Criterio = {
    id: number;
    requisito_rsc_id: number;
    item: number;
    descricao: string;
    unidade_medida: string;
    pontos: string;
    variacoes_pontuacao: Variacao[];
};

type Requisito = {
    id: number;
    numero: number;
    nome: string;
    descricao: string;
    criterios: Criterio[];
};

type Nivel = {
    id: number;
    codigo: string;
    nome: string;
    pontos_minimos: string;
    criterios_minimos: number;
    percentual_iq: string;
    escolaridade_minima: { id: number; nome: string; ordem: number };
    requisitos_obrigatorios: Requisito[];
};

type DocumentoExistente = {
    id: number;
    nome_original: string;
    tipo_documento: string;
    tamanho: number;
};

type AtividadeForm = {
    id: number | null;
    criterio_rsc_id: number | '';
    variacao_pontuacao_id: number | '';
    titulo_atividade: string;
    descricao_atividade: string;
    data_inicio: string;
    data_fim: string;
    quantidade: number;
    atividade_exercicio_cargo: boolean;
    atividade_ordinaria_cargo: boolean;
    justificativa_relevancia: string;
    usado_em_concessao_anterior: boolean;
    tipo_documento: string;
    observacao_documento: string;
    documentos_existentes_count: number;
    documentos_existentes: DocumentoExistente[];
    documentos: File[];
};

type SolicitacaoForm = {
    id: number;
    numero_protocolo: string;
    status_label: string;
    nivel_rsc_id: number;
    saldo_pontos_anterior: string | number;
    memorial: string | null;
    declaracao_veracidade: boolean;
    declaracao_nao_reutilizacao: boolean;
    atividades: AtividadeForm[];
};

type FormErrors = Record<string, string | undefined>;
type RequisitoFiltro = 'todos' | number;

const props = defineProps<{
    servidor: Record<string, any>;
    niveis: Nivel[];
    requisitos: Requisito[];
    solicitacao?: SolicitacaoForm | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Solicitação RSC', href: index.url() }],
    },
});

const isEditing = computed(() => Boolean(props.solicitacao?.id));
const filtroRequisito = ref<RequisitoFiltro>('todos');
const buscaCriterio = ref('');

const emptyAtividade = (criterioId: number | '' = ''): AtividadeForm => ({
    id: null,
    criterio_rsc_id: criterioId,
    variacao_pontuacao_id: '',
    titulo_atividade: '',
    descricao_atividade: '',
    data_inicio: '',
    data_fim: '',
    quantidade: 1,
    atividade_exercicio_cargo: true,
    atividade_ordinaria_cargo: false,
    justificativa_relevancia: '',
    usado_em_concessao_anterior: false,
    tipo_documento: 'Portaria, resolução ou ato de designação',
    observacao_documento: '',
    documentos_existentes_count: 0,
    documentos_existentes: [],
    documentos: [],
});

const initialAtividades = computed(() => {
    if (props.solicitacao?.atividades?.length) {
        return props.solicitacao.atividades.map((atividade) => ({
            ...emptyAtividade(),
            ...atividade,
            documentos: [],
            documentos_existentes: atividade.documentos_existentes ?? [],
            documentos_existentes_count: atividade.documentos_existentes_count ?? atividade.documentos_existentes?.length ?? 0,
        }));
    }

    return [emptyAtividade()];
});

const form = useForm({
    nivel_rsc_id: props.solicitacao?.nivel_rsc_id ?? props.niveis.find((nivel) => nivel.escolaridade_minima.id === props.servidor.escolaridade_id)?.id ?? props.niveis[0]?.id ?? '',
    intent: 'draft',
    saldo_pontos_anterior: props.solicitacao?.saldo_pontos_anterior ?? 0,
    memorial: props.solicitacao?.memorial ?? '',
    declaracao_veracidade: props.solicitacao?.declaracao_veracidade ?? false,
    declaracao_nao_reutilizacao: props.solicitacao?.declaracao_nao_reutilizacao ?? false,
    atividades: initialAtividades.value,
});

const selectedNivel = computed(() => props.niveis.find((nivel) => nivel.id === Number(form.nivel_rsc_id)));
const criterios = computed(() => props.requisitos.flatMap((requisito) => requisito.criterios));
const requisitosFiltrados = computed(() => {
    const termo = buscaCriterio.value.trim().toLocaleLowerCase();
    const requisitosBase = filtroRequisito.value === 'todos' ? props.requisitos : props.requisitos.filter((requisito) => requisito.id === filtroRequisito.value);

    if (!termo) {
        return requisitosBase;
    }

    return requisitosBase
        .map((requisito) => ({
            ...requisito,
            criterios: requisito.criterios.filter((criterio) =>
                [
                    `requisito ${requisito.numero}`,
                    requisito.nome,
                    requisito.descricao,
                    `${requisito.numero}.${criterio.item}`,
                    criterio.descricao,
                    criterio.unidade_medida,
                ]
                    .join(' ')
                    .toLocaleLowerCase()
                    .includes(termo),
            ),
        }))
        .filter((requisito) => requisito.criterios.length > 0);
});
const pageTitle = computed(() => (isEditing.value ? `Editar ${props.solicitacao?.numero_protocolo}` : 'Nova solicitação RSC'));
const totalCriterios = computed(() => criterios.value.length);
const criteriosVisiveis = computed(() => requisitosFiltrados.value.reduce((total, requisito) => total + requisito.criterios.length, 0));
const filtroAtivo = computed(() => filtroRequisito.value !== 'todos' || buscaCriterio.value.trim().length > 0);

function criterioById(id: number | '') {
    return criterios.value.find((criterio) => criterio.id === Number(id));
}

function requisitoById(id: number | '') {
    const criterio = criterioById(id);

    return props.requisitos.find((requisito) => requisito.id === criterio?.requisito_rsc_id);
}

function temDocumentos(atividade: AtividadeForm) {
    return atividade.documentos.length > 0 || atividade.documentos_existentes.length > 0 || atividade.documentos_existentes_count > 0;
}

function pontosDaAtividade(atividade: AtividadeForm) {
    const criterio = criterioById(atividade.criterio_rsc_id);
    const variacao = criterio?.variacoes_pontuacao.find((item) => item.id === Number(atividade.variacao_pontuacao_id));

    return Number(variacao?.pontos ?? criterio?.pontos ?? 0) * Number(atividade.quantidade || 0);
}

function atividadesDoCriterio(criterio: Criterio) {
    return form.atividades.filter((atividade) => Number(atividade.criterio_rsc_id) === criterio.id);
}

function atividadePrincipalDoCriterio(criterio: Criterio) {
    return atividadesDoCriterio(criterio)[0];
}

function quantidadeDoCriterio(criterio: Criterio) {
    return atividadesDoCriterio(criterio).reduce((total, atividade) => total + Number(atividade.quantidade || 0), 0);
}

function subtotalDoCriterio(criterio: Criterio) {
    return atividadesDoCriterio(criterio).reduce((total, atividade) => total + pontosDaAtividade(atividade), 0);
}

function pontosDoRequisito(requisito: Requisito) {
    return requisito.criterios.reduce((total, criterio) => total + subtotalDoCriterio(criterio), 0);
}

function criteriosSelecionadosDoRequisito(requisito: Requisito) {
    return requisito.criterios.filter((criterio) => quantidadeDoCriterio(criterio) > 0).length;
}

function limparFiltros() {
    filtroRequisito.value = 'todos';
    buscaCriterio.value = '';
}

const pontosDeclarados = computed(() => form.atividades.reduce((total, atividade) => total + pontosDaAtividade(atividade), 0));
const criteriosDeclarados = computed(() => new Set(form.atividades.map((atividade) => atividade.criterio_rsc_id).filter(Boolean)).size);
const atividadesPreenchidas = computed(() => form.atividades.filter((atividade) => atividade.criterio_rsc_id && atividade.titulo_atividade.trim()).length);
const pontosExigidos = computed(() => Number(selectedNivel.value?.pontos_minimos ?? 0));
const criteriosExigidos = computed(() => selectedNivel.value?.criterios_minimos ?? 0);
const saldoNecessario = computed(() => Math.max(0, pontosExigidos.value - pontosDeclarados.value));
const progressoPontuacao = computed(() => {
    if (pontosExigidos.value <= 0) {
        return 0;
    }

    return Math.min(100, (pontosDeclarados.value / pontosExigidos.value) * 100);
});
const progressoCompleto = computed(() => progressoPontuacao.value >= 100);
const criteriosCompletos = computed(() => criteriosDeclarados.value >= criteriosExigidos.value);
const serverErrorMessages = computed(() =>
    Array.from(new Set(Object.values(form.errors as FormErrors).filter((message): message is string => Boolean(message)))),
);

const bloqueios = computed(() => {
    const nivel = selectedNivel.value;
    const mensagens: string[] = [];

    if (!nivel) {
        return ['Selecione o nível RSC pleiteado.'];
    }

    if (props.servidor.estagio_probatorio) {
        mensagens.push('Servidor em estágio probatório não pode submeter.');
    }

    if (!form.memorial.trim()) {
        mensagens.push('Informe o memorial.');
    }

    if (!form.declaracao_veracidade || !form.declaracao_nao_reutilizacao) {
        mensagens.push('Confirme as declarações obrigatórias.');
    }

    if (pontosDeclarados.value < Number(nivel.pontos_minimos)) {
        mensagens.push(`Faltam ${saldoNecessario.value.toFixed(2)} pontos para o mínimo do nível.`);
    }

    if (criteriosDeclarados.value < nivel.criterios_minimos) {
        mensagens.push(`Inclua mais ${nivel.criterios_minimos - criteriosDeclarados.value} critério(s) específico(s).`);
    }

    const requisitosUsados = new Set(
        form.atividades
            .map((atividade) => criterioById(atividade.criterio_rsc_id)?.requisito_rsc_id)
            .filter(Boolean),
    );

    if (
        nivel.requisitos_obrigatorios.length > 0 &&
        !nivel.requisitos_obrigatorios.some((requisito) => requisitosUsados.has(requisito.id))
    ) {
        mensagens.push('Inclua ao menos um critério no requisito especial exigido para o nível.');
    }

    if (form.atividades.some((atividade) => atividade.criterio_rsc_id && !temDocumentos(atividade))) {
        mensagens.push('Anexe documentação em todas as atividades preenchidas.');
    }

    if (form.atividades.some((atividade) => !atividade.atividade_exercicio_cargo || atividade.atividade_ordinaria_cargo || atividade.usado_em_concessao_anterior)) {
        mensagens.push('Revise as declarações das atividades.');
    }

    return mensagens;
});

const statusNivel = computed(() => (bloqueios.value.length === 0 ? 'Apto para submissão' : 'Pendente'));

function addAtividade(criterio?: Criterio) {
    const criterioId = criterio?.id ?? '';
    const vazia = atividadeVaziaDisponivel();

    if (vazia) {
        vazia.criterio_rsc_id = criterioId;
        return;
    }

    form.atividades.push(emptyAtividade(criterioId));
}

function atividadeVaziaDisponivel() {
    return form.atividades.find(
        (atividade) =>
            !atividade.criterio_rsc_id &&
            !atividade.titulo_atividade.trim() &&
            !atividade.descricao_atividade.trim() &&
            !atividade.justificativa_relevancia.trim() &&
            !temDocumentos(atividade),
    );
}

function ensureAtividade(criterio: Criterio) {
    const existente = atividadePrincipalDoCriterio(criterio);

    if (existente) {
        return existente;
    }

    const vazia = atividadeVaziaDisponivel();

    if (vazia) {
        vazia.criterio_rsc_id = criterio.id;
        return vazia;
    }

    const atividade = emptyAtividade(criterio.id);
    form.atividades.push(atividade);

    return atividade;
}

function setQuantidadeCriterio(criterio: Criterio, event: Event) {
    const quantidade = Number((event.target as HTMLInputElement).value || 0);
    const atividades = atividadesDoCriterio(criterio);

    if (quantidade <= 0) {
        form.atividades = form.atividades.filter((atividade) => Number(atividade.criterio_rsc_id) !== criterio.id);

        if (form.atividades.length === 0) {
            form.atividades.push(emptyAtividade());
        }

        return;
    }

    const atividade = atividades[0] ?? ensureAtividade(criterio);
    atividade.quantidade = quantidade;

    atividades.slice(1).forEach((extra) => {
        const index = form.atividades.indexOf(extra);

        if (index >= 0) {
            form.atividades.splice(index, 1);
        }
    });
}

function adjustQuantidadeCriterio(criterio: Criterio, delta: number) {
    const quantidade = Math.max(0, quantidadeDoCriterio(criterio) + delta);
    const atividade = quantidade > 0 ? ensureAtividade(criterio) : atividadePrincipalDoCriterio(criterio);

    if (!atividade) {
        return;
    }

    if (quantidade <= 0) {
        form.atividades = form.atividades.filter((item) => Number(item.criterio_rsc_id) !== criterio.id);

        if (form.atividades.length === 0) {
            form.atividades.push(emptyAtividade());
        }

        return;
    }

    atividade.quantidade = quantidade;
}

function abrirDetalheCriterio(criterio: Criterio) {
    ensureAtividade(criterio);
}

function removeAtividade(index: number) {
    if (form.atividades.length > 1) {
        form.atividades.splice(index, 1);
        return;
    }

    form.atividades.splice(index, 1, emptyAtividade());
}

function setFiles(event: Event, atividade: AtividadeForm) {
    atividade.documentos = Array.from((event.target as HTMLInputElement).files ?? []);
}

function removeSelectedFile(atividade: AtividadeForm, index: number) {
    atividade.documentos.splice(index, 1);
}

function declaracaoAtividadeMarcada(atividade: AtividadeForm, field: 'atividade_exercicio_cargo' | 'atividade_ordinaria_cargo' | 'usado_em_concessao_anterior') {
    if (field === 'atividade_ordinaria_cargo' || field === 'usado_em_concessao_anterior') {
        return !atividade[field];
    }

    return atividade[field];
}

function setDeclaracaoAtividade(atividade: AtividadeForm, field: 'atividade_exercicio_cargo' | 'atividade_ordinaria_cargo' | 'usado_em_concessao_anterior', event: Event) {
    const checked = (event.target as HTMLInputElement).checked;

    if (field === 'atividade_ordinaria_cargo' || field === 'usado_em_concessao_anterior') {
        atividade[field] = !checked;
        return;
    }

    atividade[field] = checked;
}

function errorFor(path: string) {
    return (form.errors as FormErrors)[path];
}

function atividadeError(index: number, field: keyof AtividadeForm) {
    return errorFor(`atividades.${index}.${String(field)}`);
}

function atividadeDocumentosError(index: number) {
    return errorFor(`atividades.${index}.documentos`) ?? errorFor(`atividades.${index}.documentos.0`);
}

function formatBytes(bytes: number) {
    if (!bytes) {
        return '0 KB';
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function submit(intent: 'draft' | 'submit') {
    form.intent = intent;

    const transformPayload = (data: Record<string, any> & { atividades: AtividadeForm[] }) => ({
        ...data,
        atividades: data.atividades.map((atividade) => ({
            ...atividade,
            documentos_existentes_count: atividade.documentos_existentes.length,
        })),
    });

    if (props.solicitacao?.id) {
        form.transform((data) => ({
            ...transformPayload(data),
            _method: 'put',
        })).post(update.url(props.solicitacao.id), {
            forceFormData: true,
            preserveScroll: true,
        });

        return;
    }

    form.transform(transformPayload).post(store.url(), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <main class="flex flex-1 flex-col gap-6 bg-muted/20 p-3 sm:p-4 lg:p-6">
        <section class="overflow-hidden rounded-lg border bg-background shadow-xs">
            <div class="grid gap-5 p-4 sm:p-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
                <div class="grid gap-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="grid gap-2">
                            <span class="inline-flex w-fit items-center gap-2 rounded-full border bg-muted/50 px-3 py-1 text-xs font-medium text-muted-foreground">
                                <ListChecks class="size-3.5" />
                                {{ requisitos.length }} requisitos · {{ totalCriterios }} critérios ativos
                            </span>
                            <div class="grid gap-1">
                                <h1 class="text-2xl font-semibold tracking-normal sm:text-3xl">{{ pageTitle }}</h1>
                                <p class="text-sm text-muted-foreground">
                                    {{ servidor.nome }} · {{ servidor.escolaridade?.nome }}
                                    <span v-if="solicitacao"> · {{ solicitacao.status_label }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button type="button" variant="outline" :disabled="form.processing" @click="submit('draft')">
                                <Spinner v-if="form.processing && form.intent === 'draft'" />
                                Salvar rascunho
                            </Button>
                            <Button type="button" :disabled="form.processing || bloqueios.length > 0" @click="submit('submit')">
                                <Spinner v-if="form.processing && form.intent === 'submit'" />
                                Submeter
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-[minmax(0,20rem)_1fr] md:items-end">
                        <div class="grid gap-2">
                            <Label for="nivel_rsc_id">Nível pleiteado</Label>
                            <select
                                id="nivel_rsc_id"
                                v-model="form.nivel_rsc_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            >
                                <option v-for="nivel in niveis" :key="nivel.id" :value="nivel.id">
                                    {{ nivel.nome }} · mínimo {{ nivel.pontos_minimos }} pontos
                                </option>
                            </select>
                            <InputError :message="form.errors.nivel_rsc_id" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium">Progresso da pontuação</span>
                                <span :class="progressoCompleto ? 'font-semibold text-green-700 dark:text-green-400' : 'text-muted-foreground'">
                                    {{ progressoPontuacao.toFixed(0) }}%
                                </span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-muted">
                                <div
                                    :class="['h-full rounded-full transition-all', progressoCompleto ? 'bg-green-600' : 'bg-primary']"
                                    :style="{ width: `${progressoPontuacao}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-2">
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <p class="text-xs font-medium uppercase text-muted-foreground">Pontos</p>
                        <p class="mt-1 text-xl font-semibold">{{ pontosDeclarados.toFixed(2) }}</p>
                        <p class="text-xs text-muted-foreground">de {{ pontosExigidos.toFixed(2) }}</p>
                    </div>
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <p class="text-xs font-medium uppercase text-muted-foreground">Faltam</p>
                        <p class="mt-1 text-xl font-semibold">{{ saldoNecessario.toFixed(2) }}</p>
                        <p class="text-xs text-muted-foreground">pontos</p>
                    </div>
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <p class="text-xs font-medium uppercase text-muted-foreground">Critérios</p>
                        <p class="mt-1 text-xl font-semibold">{{ criteriosDeclarados }} / {{ criteriosExigidos }}</p>
                        <p class="text-xs text-muted-foreground">{{ criteriosCompletos ? 'mínimo ok' : 'em andamento' }}</p>
                    </div>
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <p class="text-xs font-medium uppercase text-muted-foreground">Status</p>
                        <p class="mt-1 text-xl font-semibold">{{ statusNivel }}</p>
                        <p class="text-xs text-muted-foreground">{{ atividadesPreenchidas }} atividade(s)</p>
                    </div>
                </div>
            </div>
        </section>

        <div v-if="serverErrorMessages.length" class="px-4 md:px-6">
            <AlertError title="Não foi possível enviar a solicitação." :errors="serverErrorMessages" />
        </div>

        <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(21rem,28rem)] xl:items-start" @submit.prevent="submit('draft')">
            <div class="grid gap-6">
                <section class="grid gap-4 rounded-lg border bg-background p-4 shadow-xs sm:p-5">
                    <div class="flex items-start gap-3">
                        <span
                            :class="[
                                'inline-flex size-10 shrink-0 items-center justify-center rounded-lg border',
                                bloqueios.length === 0 ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900 dark:bg-green-950/40 dark:text-green-400' : 'bg-muted text-muted-foreground',
                            ]"
                        >
                            <CheckCircle2 v-if="bloqueios.length === 0" class="size-5" />
                            <CircleAlert v-else class="size-5" />
                        </span>
                        <div class="grid gap-2">
                            <div>
                                <h2 class="text-lg font-semibold">Checklist da solicitação</h2>
                                <p class="text-sm text-muted-foreground">
                                    O sistema acompanha as regras automáticas enquanto você monta o pedido.
                                </p>
                            </div>
                            <div v-if="selectedNivel" class="flex flex-wrap gap-2 text-sm">
                                <span class="rounded-full border bg-muted/40 px-3 py-1">{{ selectedNivel.criterios_minimos }} critério(s) mínimo(s)</span>
                                <span class="rounded-full border bg-muted/40 px-3 py-1">{{ selectedNivel.percentual_iq }}% de IQ</span>
                                <span v-if="selectedNivel.requisitos_obrigatorios.length" class="rounded-full border bg-muted/40 px-3 py-1">Requisito especial obrigatório</span>
                            </div>
                        </div>
                    </div>

                    <ul v-if="bloqueios.length" class="grid gap-2 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                        <li v-for="bloqueio in bloqueios" :key="bloqueio">
                            {{ bloqueio }}
                        </li>
                    </ul>
                    <p v-else class="flex items-center gap-2 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950/40 dark:text-green-400">
                        <CheckCircle2 class="size-4" />
                        Critérios automáticos atendidos.
                    </p>
                </section>

                <section class="grid gap-3 rounded-lg border bg-background p-4 shadow-xs sm:p-5">
                    <div>
                        <h2 class="text-lg font-semibold">Memorial</h2>
                        <p class="text-sm text-muted-foreground">Resuma sua trajetória, as entregas relevantes e a relação com os critérios declarados.</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="memorial">Texto do memorial</Label>
                        <textarea
                            id="memorial"
                            v-model="form.memorial"
                            rows="7"
                            class="min-h-40 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        />
                        <InputError :message="form.errors.memorial" />
                    </div>
                </section>

                <section class="grid gap-4">
                    <div class="flex flex-col gap-4 rounded-lg border bg-background p-4 shadow-xs sm:p-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold">Rol de saberes e competências RSC-TAE</h2>
                                <p class="text-sm text-muted-foreground">
                                    Navegue por todos os requisitos, filtre por assunto e declare a quantidade no critério escolhido.
                                </p>
                            </div>
                            <div class="rounded-full border bg-muted/40 px-3 py-1 text-sm text-muted-foreground">
                                Exibindo {{ criteriosVisiveis }} de {{ totalCriterios }} critérios
                            </div>
                        </div>

                        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="buscaCriterio" class="h-10 pl-9" placeholder="Buscar por requisito, item, assunto ou unidade de medida" />
                            </div>
                            <Button v-if="filtroAtivo" type="button" variant="outline" @click="limparFiltros">
                                <X class="size-4" />
                                Limpar filtros
                            </Button>
                        </div>

                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <Button type="button" :variant="filtroRequisito === 'todos' ? 'default' : 'outline'" class="shrink-0" @click="filtroRequisito = 'todos'">
                                Todos
                            </Button>
                            <Button
                                v-for="requisito in requisitos"
                                :key="requisito.id"
                                type="button"
                                :variant="filtroRequisito === requisito.id ? 'default' : 'outline'"
                                class="shrink-0"
                                @click="filtroRequisito = requisito.id"
                            >
                                Req. {{ requisito.numero }}
                                <span class="rounded-full bg-background/20 px-1.5 text-xs">{{ requisito.criterios.length }}</span>
                            </Button>
                        </div>
                    </div>

                    <div v-if="requisitosFiltrados.length === 0" class="rounded-lg border bg-background p-8 text-center shadow-xs">
                        <p class="font-medium">Nenhum critério encontrado.</p>
                        <p class="mt-1 text-sm text-muted-foreground">Ajuste a busca ou volte para todos os requisitos.</p>
                    </div>

                    <div v-for="requisito in requisitosFiltrados" :key="requisito.id" class="grid gap-3">
                        <div class="flex flex-col gap-3 border-l-4 border-primary bg-background p-4 shadow-xs sm:flex-row sm:items-start sm:justify-between">
                            <div class="grid gap-1">
                                <p class="text-xs font-semibold uppercase text-muted-foreground">Requisito {{ requisito.numero }}</p>
                                <h3 class="text-lg font-semibold">{{ requisito.nome }}</h3>
                                <p class="text-sm text-muted-foreground">{{ requisito.descricao }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2 text-sm">
                                <span class="rounded-full border bg-muted/40 px-3 py-1">{{ requisito.criterios.length }} critério(s)</span>
                                <span class="rounded-full border bg-muted/40 px-3 py-1">{{ criteriosSelecionadosDoRequisito(requisito) }} selecionado(s)</span>
                                <span class="rounded-full border bg-muted/40 px-3 py-1">{{ pontosDoRequisito(requisito).toFixed(2) }} pts</span>
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <article
                                v-for="criterio in requisito.criterios"
                                :key="criterio.id"
                                :class="[
                                    'grid gap-4 rounded-lg border bg-background p-4 shadow-xs transition',
                                    quantidadeDoCriterio(criterio) > 0 ? 'border-primary/50 ring-2 ring-primary/10' : 'hover:border-primary/30',
                                ]"
                            >
                                <div class="grid gap-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-md border bg-muted/40 text-sm font-semibold">
                                            {{ requisito.numero }}.{{ criterio.item }}
                                        </span>
                                        <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                            {{ criterio.unidade_medida }}
                                        </span>
                                    </div>
                                    <p class="text-sm leading-6">{{ criterio.descricao }}</p>
                                    <div v-if="criterio.variacoes_pontuacao.length" class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                        <span v-for="variacao in criterio.variacoes_pontuacao" :key="variacao.id" class="rounded-full border bg-muted/30 px-2.5 py-1">
                                            {{ variacao.nome }} · {{ variacao.pontos }} pts
                                        </span>
                                    </div>
                                </div>

                                <div class="grid gap-3 border-t pt-4">
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="rounded-md bg-muted/40 p-3">
                                            <p class="text-xs text-muted-foreground">Pontuação base</p>
                                            <p class="font-semibold">{{ criterio.pontos }} pts</p>
                                        </div>
                                        <div class="rounded-md bg-muted/40 p-3">
                                            <p class="text-xs text-muted-foreground">Subtotal</p>
                                            <p class="font-semibold">{{ subtotalDoCriterio(criterio).toFixed(2) }} pts</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-1">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                :disabled="quantidadeDoCriterio(criterio) <= 0"
                                                @click="adjustQuantidadeCriterio(criterio, -1)"
                                            >
                                                -
                                            </Button>
                                            <Input
                                                :value="quantidadeDoCriterio(criterio)"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="h-9 w-24 text-center"
                                                @input="setQuantidadeCriterio(criterio, $event)"
                                            />
                                            <Button type="button" variant="outline" size="icon" @click="adjustQuantidadeCriterio(criterio, 1)">
                                                +
                                            </Button>
                                        </div>
                                        <Button type="button" variant="outline" @click="abrirDetalheCriterio(criterio)">
                                            <FileText class="size-4" />
                                            Detalhar
                                        </Button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="grid gap-4 xl:sticky xl:top-4">
                <section class="grid gap-4 rounded-lg border bg-background p-4 shadow-xs sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold">Atividades e comprovantes</h2>
                            <p class="text-sm text-muted-foreground">
                                Complete os detalhes dos critérios selecionados e anexe os documentos.
                            </p>
                        </div>
                        <Button type="button" variant="outline" @click="addAtividade()">
                            <Plus class="size-4" />
                            Em branco
                        </Button>
                    </div>

                    <div class="grid gap-3">
                        <article v-for="(atividade, index) in form.atividades" :key="atividade.id ?? index" class="grid gap-4 rounded-lg border bg-muted/10 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="grid gap-1">
                                    <h3 class="text-base font-semibold">Atividade {{ index + 1 }}</h3>
                                    <p v-if="criterioById(atividade.criterio_rsc_id)" class="text-sm text-muted-foreground">
                                        Requisito {{ requisitoById(atividade.criterio_rsc_id)?.numero }} · item
                                        {{ criterioById(atividade.criterio_rsc_id)?.item }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md bg-background px-2 py-1 text-sm font-medium">
                                        {{ pontosDaAtividade(atividade).toFixed(2) }} pts
                                    </span>
                                    <Button type="button" variant="ghost" size="icon" @click="removeAtividade(index)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>

                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label :for="`criterio-${index}`">Critério</Label>
                                    <select
                                        :id="`criterio-${index}`"
                                        v-model="atividade.criterio_rsc_id"
                                        class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        @change="atividade.variacao_pontuacao_id = ''"
                                    >
                                        <option value="">Selecione</option>
                                        <optgroup v-for="requisito in requisitos" :key="requisito.id" :label="`${requisito.numero}. ${requisito.nome}`">
                                            <option v-for="criterio in requisito.criterios" :key="criterio.id" :value="criterio.id">
                                                {{ requisito.numero }}.{{ criterio.item }} · {{ criterio.descricao }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <InputError :message="atividadeError(index, 'criterio_rsc_id')" />
                                </div>

                                <div v-if="criterioById(atividade.criterio_rsc_id)?.variacoes_pontuacao.length" class="grid gap-2">
                                    <Label :for="`variacao-${index}`">Variação</Label>
                                    <select
                                        :id="`variacao-${index}`"
                                        v-model="atividade.variacao_pontuacao_id"
                                        class="h-10 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    >
                                        <option value="">Selecione</option>
                                        <option
                                            v-for="variacao in criterioById(atividade.criterio_rsc_id)?.variacoes_pontuacao"
                                            :key="variacao.id"
                                            :value="variacao.id"
                                        >
                                            {{ variacao.nome }} · {{ variacao.pontos }} pontos
                                        </option>
                                    </select>
                                    <InputError :message="atividadeError(index, 'variacao_pontuacao_id')" />
                                </div>

                                <div class="grid gap-2 sm:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label :for="`quantidade-${index}`">Quantidade</Label>
                                        <Input :id="`quantidade-${index}`" v-model="atividade.quantidade" type="number" min="0.01" step="0.01" />
                                        <InputError :message="atividadeError(index, 'quantidade')" />
                                    </div>

                                    <div class="grid gap-2 sm:col-span-2">
                                        <Label :for="`titulo-${index}`">Título da atividade</Label>
                                        <Input :id="`titulo-${index}`" v-model="atividade.titulo_atividade" />
                                        <InputError :message="atividadeError(index, 'titulo_atividade')" />
                                    </div>
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label :for="`inicio-${index}`">Data inicial</Label>
                                        <Input :id="`inicio-${index}`" v-model="atividade.data_inicio" type="date" />
                                        <InputError :message="atividadeError(index, 'data_inicio')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`fim-${index}`">Data final</Label>
                                        <Input :id="`fim-${index}`" v-model="atividade.data_fim" type="date" />
                                        <InputError :message="atividadeError(index, 'data_fim')" />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`descricao-${index}`">Descrição</Label>
                                    <textarea
                                        :id="`descricao-${index}`"
                                        v-model="atividade.descricao_atividade"
                                        rows="4"
                                        class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    />
                                    <InputError :message="atividadeError(index, 'descricao_atividade')" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`relevancia-${index}`">Justificativa de relevância</Label>
                                    <textarea
                                        :id="`relevancia-${index}`"
                                        v-model="atividade.justificativa_relevancia"
                                        rows="4"
                                        class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    />
                                    <InputError :message="atividadeError(index, 'justificativa_relevancia')" />
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label :for="`tipo-documento-${index}`">Tipo de documento</Label>
                                        <Input :id="`tipo-documento-${index}`" v-model="atividade.tipo_documento" />
                                        <InputError :message="atividadeError(index, 'tipo_documento')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`observacao-documento-${index}`">Observação do documento</Label>
                                        <Input :id="`observacao-documento-${index}`" v-model="atividade.observacao_documento" />
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 rounded-md border bg-muted/30 p-3">
                                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                    <div>
                                        <h4 class="flex items-center gap-2 text-sm font-medium">
                                            <Paperclip class="size-4" />
                                            Comprovante da atividade
                                        </h4>
                                        <p class="text-sm text-muted-foreground">
                                            Arquivos PDF, JPG, PNG ou WEBP até 10 MB por arquivo.
                                        </p>
                                    </div>
                                    <Label
                                        :for="`documentos-${index}`"
                                        class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md border border-input bg-background px-3 text-sm font-medium shadow-xs hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <Upload class="size-4" />
                                        Selecionar arquivos
                                    </Label>
                                    <Input
                                        :id="`documentos-${index}`"
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                        class="sr-only"
                                        @change="setFiles($event, atividade)"
                                    />
                                </div>

                                <div v-if="atividade.documentos_existentes.length" class="grid gap-2">
                                    <p class="text-xs font-medium uppercase text-muted-foreground">Já anexados</p>
                                    <div class="grid gap-2">
                                        <div v-for="documento in atividade.documentos_existentes" :key="documento.id" class="flex items-center justify-between gap-3 rounded-md bg-background p-2 text-sm">
                                            <span class="flex min-w-0 items-center gap-2">
                                                <FileText class="size-4 shrink-0" />
                                                <span class="truncate">{{ documento.nome_original }}</span>
                                            </span>
                                            <span class="shrink-0 text-xs text-muted-foreground">{{ formatBytes(documento.tamanho) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="atividade.documentos.length" class="grid gap-2">
                                    <p class="text-xs font-medium uppercase text-muted-foreground">Novos anexos</p>
                                    <div class="grid gap-2">
                                        <div v-for="(documento, fileIndex) in atividade.documentos" :key="`${documento.name}-${fileIndex}`" class="flex items-center justify-between gap-3 rounded-md bg-background p-2 text-sm">
                                            <span class="flex min-w-0 items-center gap-2">
                                                <FileText class="size-4 shrink-0" />
                                                <span class="truncate">{{ documento.name }}</span>
                                            </span>
                                            <Button type="button" variant="ghost" size="icon" @click="removeSelectedFile(atividade, fileIndex)">
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <InputError :message="atividadeDocumentosError(index)" />
                            </div>

                            <div class="grid gap-3 rounded-md border bg-background p-3 text-sm">
                                <h4 class="text-sm font-medium">Declarações da atividade</h4>
                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 size-4 rounded border-input"
                                        :checked="declaracaoAtividadeMarcada(atividade, 'atividade_exercicio_cargo')"
                                        @change="setDeclaracaoAtividade(atividade, 'atividade_exercicio_cargo', $event)"
                                    />
                                    <span>A atividade foi realizada no exercício do cargo.</span>
                                </label>
                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 size-4 rounded border-input"
                                        :checked="declaracaoAtividadeMarcada(atividade, 'atividade_ordinaria_cargo')"
                                        @change="setDeclaracaoAtividade(atividade, 'atividade_ordinaria_cargo', $event)"
                                    />
                                    <span>A atividade não é ordinária ou rotineira do cargo.</span>
                                </label>
                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 size-4 rounded border-input"
                                        :checked="declaracaoAtividadeMarcada(atividade, 'usado_em_concessao_anterior')"
                                        @change="setDeclaracaoAtividade(atividade, 'usado_em_concessao_anterior', $event)"
                                    />
                                    <span>A atividade não foi utilizada em concessão anterior.</span>
                                </label>
                            </div>

                            <InputError :message="atividadeError(index, 'atividade_exercicio_cargo')" />
                            <InputError :message="atividadeError(index, 'atividade_ordinaria_cargo')" />
                            <InputError :message="atividadeError(index, 'usado_em_concessao_anterior')" />
                        </article>
                    </div>
                </section>

                <section class="grid gap-3 rounded-lg border bg-background p-4 text-sm shadow-xs sm:p-5">
                    <div class="flex items-center gap-2">
                        <Target class="size-4 text-muted-foreground" />
                        <h2 class="font-semibold">Declarações finais</h2>
                    </div>
                    <label class="flex items-start gap-3">
                        <input v-model="form.declaracao_veracidade" type="checkbox" class="mt-0.5 size-4 rounded border-input" />
                        Declaro que as informações e documentos apresentados são verdadeiros.
                    </label>
                    <label class="flex items-start gap-3">
                        <input v-model="form.declaracao_nao_reutilizacao" type="checkbox" class="mt-0.5 size-4 rounded border-input" />
                        Declaro que as atividades e pontos não foram utilizados em concessões anteriores.
                    </label>
                    <InputError :message="form.errors.solicitacao" />
                    <InputError :message="form.errors.declaracao_veracidade" />
                    <InputError :message="form.errors.declaracao_nao_reutilizacao" />
                </section>
            </aside>
        </form>
    </main>
</template>
