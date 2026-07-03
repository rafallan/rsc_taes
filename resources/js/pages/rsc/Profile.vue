<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/rsc/profile';

type Escolaridade = {
    id: number;
    nome: string;
    ordem: number;
};

const props = defineProps<{
    servidor: Record<string, any> | null;
    escolaridades: Escolaridade[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Perfil funcional', href: update.url() }],
    },
});

const form = useForm({
    escolaridade_id: props.servidor?.escolaridade_id ?? '',
    nome: props.servidor?.nome ?? '',
    siape: props.servidor?.siape ?? '',
    cpf: props.servidor?.cpf ?? '',
    email_institucional: props.servidor?.email_institucional ?? '',
    cargo: props.servidor?.cargo ?? '',
    unidade_lotacao: props.servidor?.unidade_lotacao ?? '',
    data_ingresso_cargo: props.servidor?.data_ingresso_cargo ?? '',
    estagio_probatorio: props.servidor?.estagio_probatorio ?? false,
    ativo: true,
});

function submit() {
    form.put(update.url(), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Perfil funcional RSC" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-normal">Perfil funcional</h1>
            <p class="text-sm text-muted-foreground">
                Dados usados para validar a escolaridade, estágio probatório e identificação do requerimento.
            </p>
        </div>

        <form class="grid max-w-5xl gap-5 md:grid-cols-2" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="nome">Nome</Label>
                <Input id="nome" v-model="form.nome" name="nome" autocomplete="name" />
                <InputError :message="form.errors.nome" />
            </div>

            <div class="grid gap-2">
                <Label for="email_institucional">E-mail institucional</Label>
                <Input id="email_institucional" v-model="form.email_institucional" name="email_institucional" type="email" />
                <InputError :message="form.errors.email_institucional" />
            </div>

            <div class="grid gap-2">
                <Label for="siape">SIAPE</Label>
                <Input id="siape" v-model="form.siape" name="siape" />
                <InputError :message="form.errors.siape" />
            </div>

            <div class="grid gap-2">
                <Label for="cpf">CPF</Label>
                <Input id="cpf" v-model="form.cpf" name="cpf" />
                <InputError :message="form.errors.cpf" />
            </div>

            <div class="grid gap-2">
                <Label for="cargo">Cargo</Label>
                <Input id="cargo" v-model="form.cargo" name="cargo" />
                <InputError :message="form.errors.cargo" />
            </div>

            <div class="grid gap-2">
                <Label for="unidade_lotacao">Unidade de lotação</Label>
                <Input id="unidade_lotacao" v-model="form.unidade_lotacao" name="unidade_lotacao" />
                <InputError :message="form.errors.unidade_lotacao" />
            </div>

            <div class="grid gap-2">
                <Label for="escolaridade_id">Escolaridade formal</Label>
                <select
                    id="escolaridade_id"
                    v-model="form.escolaridade_id"
                    name="escolaridade_id"
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                >
                    <option value="">Selecione</option>
                    <option v-for="escolaridade in escolaridades" :key="escolaridade.id" :value="escolaridade.id">
                        {{ escolaridade.nome }}
                    </option>
                </select>
                <InputError :message="form.errors.escolaridade_id" />
            </div>

            <div class="grid gap-2">
                <Label for="data_ingresso_cargo">Data de ingresso no cargo</Label>
                <Input id="data_ingresso_cargo" v-model="form.data_ingresso_cargo" name="data_ingresso_cargo" type="date" />
                <InputError :message="form.errors.data_ingresso_cargo" />
            </div>

            <label class="flex items-center gap-3 rounded-md border p-3 text-sm md:col-span-2">
                <input v-model="form.estagio_probatorio" type="checkbox" class="size-4 rounded border-input" />
                Servidor em estágio probatório
            </label>
            <InputError class="md:col-span-2" :message="form.errors.estagio_probatorio" />

            <div class="flex justify-end md:col-span-2">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Salvar perfil
                </Button>
            </div>
        </form>
    </main>
</template>
