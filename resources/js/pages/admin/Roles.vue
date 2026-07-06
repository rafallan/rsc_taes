<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy, index, store, update } from '@/routes/admin/roles';

type Permission = {
    id: number;
    name: string;
};

type RoleRow = {
    id: number;
    name: string;
    users_count: number;
    permissions: string[];
};

const props = defineProps<{
    roles: RoleRow[];
    permissions: Permission[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Papéis', href: index() }],
    },
});

const editingRole = ref<RoleRow | null>(null);
const isEditing = computed(() => editingRole.value !== null);

const form = useForm({
    name: '',
    permissions: [] as string[],
});

function resetForm() {
    editingRole.value = null;
    form.reset();
    form.clearErrors();
}

function editRole(role: RoleRow) {
    editingRole.value = role;
    form.name = role.name;
    form.permissions = [...role.permissions];
    form.clearErrors();
}

function submit() {
    if (editingRole.value) {
        form.put(update.url(editingRole.value.id), {
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

function togglePermission(permission: string, checked: boolean) {
    form.permissions = checked
        ? [...new Set([...form.permissions, permission])]
        : form.permissions.filter((item) => item !== permission);
}
</script>

<template>
    <Head title="Administração de papéis" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-normal">Papéis</h1>
            <p class="text-sm text-muted-foreground">
                Agrupe permissões para administrar acessos.
            </p>
        </div>

        <section class="grid gap-4 rounded-lg border p-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-medium">{{ isEditing ? 'Editar papel' : 'Novo papel' }}</h2>
                <Button v-if="isEditing" type="button" variant="ghost" @click="resetForm">
                    <X />
                    Cancelar
                </Button>
            </div>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-3">
                    <Label>Permissões</Label>
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                        <label v-for="permission in permissions" :key="permission.id" class="flex items-center gap-2 rounded-md border p-3 text-sm">
                            <input
                                type="checkbox"
                                class="size-4 rounded border-input"
                                :checked="form.permissions.includes(permission.name)"
                                @change="togglePermission(permission.name, ($event.target as HTMLInputElement).checked)"
                            />
                            {{ permission.name }}
                        </label>
                    </div>
                    <InputError :message="form.errors.permissions" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        <Plus v-else-if="!isEditing" />
                        <Pencil v-else />
                        {{ isEditing ? 'Atualizar papel' : 'Criar papel' }}
                    </Button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/60 text-left">
                    <tr>
                        <th class="p-3 font-medium">Papel</th>
                        <th class="p-3 font-medium">Permissões</th>
                        <th class="w-32 p-3 text-center font-medium">Usuários</th>
                        <th class="w-40 p-3 text-right font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="role in roles" :key="role.id" class="border-t">
                        <td class="p-3 font-medium">{{ role.name }}</td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-2">
                                <span v-for="permission in role.permissions" :key="permission" class="rounded-md bg-muted px-2 py-1 text-xs">
                                    {{ permission }}
                                </span>
                            </div>
                        </td>
                        <td class="p-3 text-center">{{ role.users_count }}</td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" size="icon" @click="editRole(role)">
                                    <Pencil />
                                </Button>
                                <Button type="button" variant="destructive" size="icon" :disabled="role.name === 'Administrador'" @click="form.delete(destroy.url(role.id), { preserveScroll: true })">
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
