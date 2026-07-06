<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy, index, store, update } from '@/routes/admin/permissions';

type PermissionRow = {
    id: number;
    name: string;
    guard_name: string;
    roles_count: number;
};

const props = defineProps<{
    permissions: PermissionRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Permissões', href: index() }],
    },
});

const editingPermission = ref<PermissionRow | null>(null);
const isEditing = computed(() => editingPermission.value !== null);

const form = useForm({
    name: '',
});

function resetForm() {
    editingPermission.value = null;
    form.reset();
    form.clearErrors();
}

function editPermission(permission: PermissionRow) {
    editingPermission.value = permission;
    form.name = permission.name;
    form.clearErrors();
}

function submit() {
    if (editingPermission.value) {
        form.put(update.url(editingPermission.value.id), {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });

        return;
    }

    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
}
</script>

<template>
    <Head title="Administração de permissões" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-normal">Permissões</h1>
            <p class="text-sm text-muted-foreground">
                Cadastre capacidades usadas nos papéis do sistema.
            </p>
        </div>

        <section class="grid gap-4 rounded-lg border p-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-medium">{{ isEditing ? 'Editar permissão' : 'Nova permissão' }}</h2>
                <Button v-if="isEditing" type="button" variant="ghost" @click="resetForm">
                    <X />
                    Cancelar
                </Button>
            </div>

            <form class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    <Plus v-else-if="!isEditing" />
                    <Pencil v-else />
                    {{ isEditing ? 'Atualizar' : 'Criar' }}
                </Button>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/60 text-left">
                    <tr>
                        <th class="p-3 font-medium">Permissão</th>
                        <th class="w-32 p-3 text-center font-medium">Papéis</th>
                        <th class="w-40 p-3 text-right font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="permission in permissions" :key="permission.id" class="border-t">
                        <td class="p-3">
                            <p class="font-medium">{{ permission.name }}</p>
                            <p class="text-muted-foreground">{{ permission.guard_name }}</p>
                        </td>
                        <td class="p-3 text-center">{{ permission.roles_count }}</td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" size="icon" @click="editPermission(permission)">
                                    <Pencil />
                                </Button>
                                <Button type="button" variant="destructive" size="icon" @click="form.delete(destroy.url(permission.id), { preserveScroll: true })">
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
</template>
