<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, ClipboardList, FolderGit2, KeyRound, LayoutGrid, ShieldCheck, UserRoundCog, Users } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as adminPermissionsIndex } from '@/routes/admin/permissions';
import { index as adminRolesIndex } from '@/routes/admin/roles';
import { index as adminUsersIndex } from '@/routes/admin/users';
import { edit as rscProfileEdit } from '@/routes/rsc/profile';
import { index as rscSolicitacoesIndex } from '@/routes/rsc/solicitacoes';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => Boolean((page.props.auth as { isAdmin?: boolean } | undefined)?.isAdmin));

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Solicitações RSC',
        href: rscSolicitacoesIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Perfil funcional',
        href: rscProfileEdit(),
        icon: UserRoundCog,
    },
    ...(isAdmin.value
        ? [
              {
                  title: 'Usuários',
                  href: adminUsersIndex(),
                  icon: Users,
              },
              {
                  title: 'Papéis',
                  href: adminRolesIndex(),
                  icon: ShieldCheck,
              },
              {
                  title: 'Permissões',
                  href: adminPermissionsIndex(),
                  icon: KeyRound,
              },
          ]
        : []),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
