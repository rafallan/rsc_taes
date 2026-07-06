<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy, index, store, update } from '@/routes/admin/users';

type Role = {
    id: number;
    name: string;
};

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string | null;
};

const props = defineProps<{
    users: UserRow[];
    roles: Role[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuários', href: index() }],
    },
});

const editingUser = ref<UserRow | null>(null);
const isEditing = computed(() => editingUser.value !== null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

function resetForm() {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
}

function editUser(user: UserRow) {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.roles = [...user.roles];
    form.clearErrors();
}

function submit() {
    if (editingUser.value) {
        form.put(update.url(editingUser.value.id), {
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

function toggleRole(role: string, checked: boolean) {
    form.roles = checked ? [...new Set([...form.roles, role])] : form.roles.filter((item) => item !== role);
}
</script>

<template>
    <Head title="Administração de usuários" />

    <main class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-normal">Usuários</h1>
            <p class="text-sm text-muted-foreground">
                Gerencie contas e vincule papéis de acesso.
            </p>
        </div>

        <section class="grid gap-4 rounded-lg border p-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-medium">{{ isEditing ? 'Editar usuário' : 'Novo usuário' }}</h2>
                <Button v-if="isEditing" type="button" variant="ghost" @click="resetForm">
                    <X />
                    Cancelar
                </Button>
            </div>

            <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">E-mail</Label>
                    <Input id="email" v-model="form.email" type="email" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Senha</Label>
                    <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmar senha</Label>
                    <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                </div>

                <div class="grid gap-3 lg:col-span-2">
                    <Label>Papéis</Label>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 rounded-md border p-3 text-sm">
                            <input
                                type="checkbox"
                                class="size-4 rounded border-input"
                                :checked="form.roles.includes(role.name)"
                                @change="toggleRole(role.name, ($event.target as HTMLInputElement).checked)"
                            />
                            {{ role.name }}
                        </label>
                    </div>
                    <InputError :message="form.errors.roles" />
                </div>

                <div class="flex justify-end lg:col-span-2">
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        <Plus v-else-if="!isEditing" />
                        <Pencil v-else />
                        {{ isEditing ? 'Atualizar usuário' : 'Criar usuário' }}
                    </Button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/60 text-left">
                    <tr>
                        <th class="p-3 font-medium">Usuário</th>
                        <th class="p-3 font-medium">Papéis</th>
                        <th class="w-40 p-3 text-right font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" class="border-t">
                        <td class="p-3">
                            <p class="font-medium">{{ user.name }}</p>
                            <p class="text-muted-foreground">{{ user.email }}</p>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-2">
                                <span v-for="role in user.roles" :key="role" class="rounded-md bg-muted px-2 py-1 text-xs">
                                    {{ role }}
                                </span>
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" size="icon" @click="editUser(user)">
                                    <Pencil />
                                </Button>
                                <Button type="button" variant="destructive" size="icon" @click="form.delete(destroy.url(user.id), { preserveScroll: true })">
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
