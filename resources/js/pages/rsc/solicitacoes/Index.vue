<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { create, edit, show } from '@/routes/rsc/solicitacoes';

defineProps<{
    servidor: Record<string, any>;
    solicitacoes: Record<string, any>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Solicitações RSC', href: create.url() }],
    },
});
</script>

<template>
    <Head title="Solicitações RSC" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-semibold tracking-normal">Solicitações RSC</h1>
                <p class="text-sm text-muted-foreground">
                    {{ servidor.nome }} · {{ servidor.escolaridade?.nome }}
                </p>
            </div>

            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    Nova solicitação
                </Link>
            </Button>
        </div>

        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/60 text-left">
                    <tr>
                        <th class="p-3 font-medium">Protocolo</th>
                        <th class="p-3 font-medium">Nível</th>
                        <th class="p-3 font-medium">Pontos</th>
                        <th class="p-3 font-medium">Critérios</th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="solicitacoes.length === 0">
                        <td colspan="6" class="p-6 text-center text-muted-foreground">
                            Nenhuma solicitação registrada.
                        </td>
                    </tr>
                    <tr v-for="solicitacao in solicitacoes" :key="solicitacao.id" class="border-t">
                        <td class="p-3">
                            <Link class="font-medium underline underline-offset-4" :href="show(solicitacao.id)">
                                {{ solicitacao.numero_protocolo }}
                            </Link>
                        </td>
                        <td class="p-3">{{ solicitacao.nivel.nome }}</td>
                        <td class="p-3">{{ solicitacao.pontos_declarados }}</td>
                        <td class="p-3">{{ solicitacao.criterios_declarados }}</td>
                        <td class="p-3">{{ solicitacao.status_label }}</td>
                        <td class="p-3">
                            <Button v-if="solicitacao.can_edit" variant="outline" as-child>
                                <Link :href="edit(solicitacao.id)">
                                    <Pencil />
                                    Editar
                                </Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</template>
