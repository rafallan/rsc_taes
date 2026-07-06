<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Users', [
            'users' => User::query()
                ->with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->values(),
                    'created_at' => $user->created_at?->toDateTimeString(),
                ]),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['roles']);

        return to_route('admin.users.index')->with('success', 'Usuário criado.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $roles = array_map(static fn (mixed $role): string => (string) $role, $validated['roles']);

        if ($request->user()?->is($user) && ! in_array('Administrador', $roles, true)) {
            return back()->withErrors(['roles' => 'Você não pode remover o próprio papel de Administrador.']);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...filled($validated['password'] ?? null) ? ['password' => Hash::make($validated['password'])] : [],
        ]);

        $user->syncRoles($roles);

        return to_route('admin.users.index')->with('success', 'Usuário atualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->user()?->is($user)) {
            return back()->withErrors(['user' => 'Você não pode excluir o próprio usuário.']);
        }

        $user->delete();

        return to_route('admin.users.index')->with('success', 'Usuário excluído.');
    }
}
