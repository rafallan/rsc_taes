<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/rsc/solicitacoes';

defineProps<{
    solicitacao: Record<string, any>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Detalhe da solicitação', href: index() }],
    },
});
</script>

<template>
    <Head :title="`Solicitação ${solicitacao.numero_protocolo}`" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">{{ solicitacao.numero_protocolo }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ solicitacao.nivel.nome }} · {{ solicitacao.status_label }}
                </p>
            </div>

            <Button variant="outline" as-child>
                <Link :href="index()">
                    <ArrowLeft />
                    Voltar
                </Link>
            </Button>
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Pontos declarados</p>
                <p class="text-3xl font-semibold">{{ solicitacao.pontos_declarados }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Critérios distintos</p>
                <p class="text-3xl font-semibold">{{ solicitacao.criterios_declarados }}</p>
            </div>
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Percentual IQ</p>
                <p class="text-3xl font-semibold">{{ solicitacao.nivel.percentual_iq }}%</p>
            </div>
        </section>

        <section class="grid gap-3">
            <h2 class="text-lg font-medium">Atividades declaradas</h2>
            <article v-for="item in solicitacao.criterios" :key="item.id" class="grid gap-3 rounded-lg border p-4">
                <div class="flex flex-col justify-between gap-2 md:flex-row">
                    <div>
                        <h3 class="font-medium">{{ item.titulo_atividade }}</h3>
                        <p class="text-sm text-muted-foreground">
                            Requisito {{ item.criterio.requisito.numero }} · item {{ item.criterio.item }}
                        </p>
                    </div>
                    <p class="text-sm font-medium">{{ item.pontos_calculados }} pontos</p>
                </div>
                <p class="text-sm">{{ item.descricao_atividade }}</p>
                <p class="text-sm text-muted-foreground">{{ item.justificativa_relevancia }}</p>
                <p class="text-sm text-muted-foreground">
                    {{ item.documentos_count }} documento(s) anexado(s)
                </p>
            </article>
        </section>

        <section class="grid gap-3">
            <h2 class="text-lg font-medium">Memorial</h2>
            <div class="whitespace-pre-wrap rounded-lg border p-4 text-sm">
                {{ solicitacao.memorial }}
            </div>
        </section>
    </main>
</template>
