<?php

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('non administrator cannot access admin users', function () {
    $this->seed(AccessControlSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Servidor');

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('administrator can access admin users', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('administrator can create permission and role', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.permissions.store'), [
            'name' => 'gerenciar relatorios',
        ])
        ->assertRedirect(route('admin.permissions.index'));

    $permission = Permission::query()->where('name', 'gerenciar relatorios')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'Auditoria',
            'permissions' => ['gerenciar relatorios'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'Auditoria')->firstOrFail();

    expect($role->hasPermissionTo($permission))->toBeTrue();
});

test('administrator can create and update users roles', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Membro Comissão',
            'email' => 'comissao@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => ['Comissão'],
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'comissao@example.com')->firstOrFail();

    expect($user->hasRole('Comissão'))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'name' => 'Servidor RSC',
            'email' => 'servidor-rsc@example.com',
            'roles' => ['Servidor'],
        ])
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->hasRole('Servidor'))->toBeTrue()
        ->and($user->hasRole('Comissão'))->toBeFalse();
});

function adminUser(): User
{
    $user = User::factory()->create();

    Artisan::call('db:seed', ['--class' => AccessControlSeeder::class]);

    $user->assignRole('Administrador');

    return $user;
}
