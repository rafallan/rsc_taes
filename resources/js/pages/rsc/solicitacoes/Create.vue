<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/rsc/solicitacoes';

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

type AtividadeForm = {
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
    documentos: File[];
};

const props = defineProps<{
    servidor: Record<string, any>;
    niveis: Nivel[];
    requisitos: Requisito[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Nova solicitação RSC', href: store.url() }],
    },
});

const emptyAtividade = (): AtividadeForm => ({
    criterio_rsc_id: '',
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
    documentos: [],
});

const form = useForm({
    nivel_rsc_id: props.niveis.find((nivel) => nivel.escolaridade_minima.id === props.servidor.escolaridade_id)?.id ?? props.niveis[0]?.id ?? '',
    intent: 'draft',
    saldo_pontos_anterior: 0,
    memorial: '',
    declaracao_veracidade: false,
    declaracao_nao_reutilizacao: false,
    atividades: [emptyAtividade()],
});

const selectedNivel = computed(() => props.niveis.find((nivel) => nivel.id === Number(form.nivel_rsc_id)));

const criterios = computed(() => props.requisitos.flatMap((requisito) => requisito.criterios));

function criterioById(id: number | '') {
    return criterios.value.find((criterio) => criterio.id === Number(id));
}

function pontosDaAtividade(atividade: AtividadeForm) {
    const criterio = criterioById(atividade.criterio_rsc_id);
    const variacao = criterio?.variacoes_pontuacao.find((item) => item.id === Number(atividade.variacao_pontuacao_id));

    return Number(variacao?.pontos ?? criterio?.pontos ?? 0) * Number(atividade.quantidade || 0);
}

const pontosDeclarados = computed(() => form.atividades.reduce((total, atividade) => total + pontosDaAtividade(atividade), 0));

const criteriosDeclarados = computed(() => new Set(form.atividades.map((atividade) => atividade.criterio_rsc_id).filter(Boolean)).size);

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
        mensagens.push(`Pontuação mínima: ${nivel.pontos_minimos}.`);
    }

    if (criteriosDeclarados.value < nivel.criterios_minimos) {
        mensagens.push(`Critérios específicos mínimos: ${nivel.criterios_minimos}.`);
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

    if (form.atividades.some((atividade) => atividade.documentos.length === 0)) {
        mensagens.push('Anexe documentação em todas as atividades.');
    }

    if (form.atividades.some((atividade) => !atividade.atividade_exercicio_cargo || atividade.atividade_ordinaria_cargo || atividade.usado_em_concessao_anterior)) {
        mensagens.push('Revise as declarações das atividades.');
    }

    return mensagens;
});

function addAtividade() {
    form.atividades.push(emptyAtividade());
}

function removeAtividade(index: number) {
    if (form.atividades.length > 1) {
        form.atividades.splice(index, 1);
    }
}

function setFiles(event: Event, atividade: AtividadeForm) {
    atividade.documentos = Array.from((event.target as HTMLInputElement).files ?? []);
}

function submit(intent: 'draft' | 'submit') {
    form.intent = intent;
    form.post(store.url(), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Nova solicitação RSC" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-normal">Nova solicitação RSC</h1>
            <p class="text-sm text-muted-foreground">
                Preencha as atividades, anexe os comprovantes e acompanhe a pontuação antes de submeter.
            </p>
        </div>

        <form class="grid gap-6 xl:grid-cols-[1fr_22rem]" @submit.prevent="submit('draft')">
            <section class="flex flex-col gap-5">
                <div class="grid gap-2">
                    <Label for="nivel_rsc_id">Nível pleiteado</Label>
                    <select
                        id="nivel_rsc_id"
                        v-model="form.nivel_rsc_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option v-for="nivel in niveis" :key="nivel.id" :value="nivel.id">
                            {{ nivel.nome }} · mínimo {{ nivel.pontos_minimos }} pontos
                        </option>
                    </select>
                    <InputError :message="form.errors.nivel_rsc_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="memorial">Memorial</Label>
                    <textarea
                        id="memorial"
                        v-model="form.memorial"
                        rows="8"
                        class="min-h-40 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    />
                    <InputError :message="form.errors.memorial" />
                </div>

                <div v-for="(atividade, index) in form.atividades" :key="index" class="grid gap-4 rounded-lg border p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-medium">Atividade {{ index + 1 }}</h2>
                        <Button type="button" variant="ghost" size="icon" :disabled="form.atividades.length === 1" @click="removeAtividade(index)">
                            <Trash2 />
                        </Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label :for="`criterio-${index}`">Critério</Label>
                            <select
                                :id="`criterio-${index}`"
                                v-model="atividade.criterio_rsc_id"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                @change="atividade.variacao_pontuacao_id = ''"
                            >
                                <option value="">Selecione</option>
                                <optgroup v-for="requisito in requisitos" :key="requisito.id" :label="`${requisito.numero}. ${requisito.nome}`">
                                    <option v-for="criterio in requisito.criterios" :key="criterio.id" :value="criterio.id">
                                        {{ requisito.numero }}.{{ criterio.item }} · {{ criterio.descricao }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <div v-if="criterioById(atividade.criterio_rsc_id)?.variacoes_pontuacao.length" class="grid gap-2">
                            <Label :for="`variacao-${index}`">Variação</Label>
                            <select
                                :id="`variacao-${index}`"
                                v-model="atividade.variacao_pontuacao_id"
                                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
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
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`quantidade-${index}`">Quantidade</Label>
                            <Input :id="`quantidade-${index}`" v-model="atividade.quantidade" type="number" min="0.01" step="0.01" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label :for="`titulo-${index}`">Título da atividade</Label>
                            <Input :id="`titulo-${index}`" v-model="atividade.titulo_atividade" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`inicio-${index}`">Data inicial</Label>
                            <Input :id="`inicio-${index}`" v-model="atividade.data_inicio" type="date" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`fim-${index}`">Data final</Label>
                            <Input :id="`fim-${index}`" v-model="atividade.data_fim" type="date" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label :for="`descricao-${index}`">Descrição</Label>
                            <textarea
                                :id="`descricao-${index}`"
                                v-model="atividade.descricao_atividade"
                                rows="4"
                                class="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label :for="`relevancia-${index}`">Justificativa de relevância</Label>
                            <textarea
                                :id="`relevancia-${index}`"
                                v-model="atividade.justificativa_relevancia"
                                rows="4"
                                class="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`tipo-documento-${index}`">Tipo de documento</Label>
                            <Input :id="`tipo-documento-${index}`" v-model="atividade.tipo_documento" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`documentos-${index}`">Documentos</Label>
                            <Input :id="`documentos-${index}`" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" @change="setFiles($event, atividade)" />
                        </div>
                    </div>

                    <div class="grid gap-3 text-sm">
                        <label class="flex items-center gap-3">
                            <input v-model="atividade.atividade_exercicio_cargo" type="checkbox" class="size-4 rounded border-input" />
                            Atividade realizada no exercício do cargo
                        </label>
                        <label class="flex items-center gap-3">
                            <input v-model="atividade.atividade_ordinaria_cargo" type="checkbox" class="size-4 rounded border-input" />
                            Atividade exclusivamente ordinária do cargo
                        </label>
                        <label class="flex items-center gap-3">
                            <input v-model="atividade.usado_em_concessao_anterior" type="checkbox" class="size-4 rounded border-input" />
                            Atividade já usada em concessão anterior
                        </label>
                    </div>

                    <div class="rounded-md bg-muted p-3 text-sm">
                        {{ pontosDaAtividade(atividade).toFixed(2) }} pontos calculados
                    </div>
                </div>

                <Button type="button" variant="outline" class="self-start" @click="addAtividade">
                    <Plus />
                    Adicionar atividade
                </Button>

                <div class="grid gap-3 rounded-lg border p-4 text-sm">
                    <label class="flex items-start gap-3">
                        <input v-model="form.declaracao_veracidade" type="checkbox" class="mt-0.5 size-4 rounded border-input" />
                        Declaro que as informações e documentos apresentados são verdadeiros.
                    </label>
                    <label class="flex items-start gap-3">
                        <input v-model="form.declaracao_nao_reutilizacao" type="checkbox" class="mt-0.5 size-4 rounded border-input" />
                        Declaro que as atividades e pontos não foram utilizados em concessões anteriores.
                    </label>
                </div>

                <InputError :message="form.errors.solicitacao" />
            </section>

            <aside class="flex h-fit flex-col gap-4 rounded-lg border p-4 xl:sticky xl:top-6">
                <div>
                    <p class="text-sm text-muted-foreground">Pontuação declarada</p>
                    <p class="text-3xl font-semibold">{{ pontosDeclarados.toFixed(2) }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-md bg-muted p-3">
                        <p class="text-muted-foreground">Critérios</p>
                        <p class="text-xl font-medium">{{ criteriosDeclarados }}</p>
                    </div>
                    <div class="rounded-md bg-muted p-3">
                        <p class="text-muted-foreground">IQ</p>
                        <p class="text-xl font-medium">{{ selectedNivel?.percentual_iq ?? '0' }}%</p>
                    </div>
                </div>

                <div v-if="selectedNivel" class="text-sm text-muted-foreground">
                    {{ selectedNivel.nome }} exige {{ selectedNivel.pontos_minimos }} pontos e
                    {{ selectedNivel.criterios_minimos }} critérios específicos.
                </div>

                <ul v-if="bloqueios.length" class="grid gap-2 text-sm text-destructive">
                    <li v-for="bloqueio in bloqueios" :key="bloqueio">· {{ bloqueio }}</li>
                </ul>

                <div class="grid gap-2">
                    <Button type="submit" variant="outline" :disabled="form.processing">
                        <Spinner v-if="form.processing && form.intent === 'draft'" />
                        Salvar rascunho
                    </Button>
                    <Button type="button" :disabled="form.processing || bloqueios.length > 0" @click="submit('submit')">
                        <Spinner v-if="form.processing && form.intent === 'submit'" />
                        Submeter
                    </Button>
                </div>
            </aside>
        </form>
    </main>
</template>
