<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'administrar usuarios',
            'administrar papeis',
            'administrar permissoes',
            'avaliar solicitacoes rsc',
            'submeter solicitacoes rsc',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]),
        ]);

        $administrador = Role::query()->firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $comissao = Role::query()->firstOrCreate(['name' => 'Comissão', 'guard_name' => 'web']);
        $servidor = Role::query()->firstOrCreate(['name' => 'Servidor', 'guard_name' => 'web']);

        $administrador->syncPermissions($permissions->values());
        $comissao->syncPermissions([
            $permissions['avaliar solicitacoes rsc'],
        ]);
        $servidor->syncPermissions([
            $permissions['submeter solicitacoes rsc'],
        ]);

        $firstUser = User::query()->orderBy('id')->first();

        if ($firstUser && ! User::role('Administrador')->exists()) {
            $firstUser->assignRole($administrador);
        }

        User::query()
            ->whereDoesntHave('roles')
            ->get()
            ->each(fn (User $user): mixed => $user->assignRole($servidor));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
