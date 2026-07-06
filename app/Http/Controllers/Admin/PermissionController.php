<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Permissions', [
            'permissions' => Permission::query()
                ->withCount('roles')
                ->orderBy('name')
                ->get(['id', 'name', 'guard_name'])
                ->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'roles_count' => $permission->roles_count,
                ]),
        ]);
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        Permission::query()->create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        return to_route('admin.permissions.index')->with('success', 'Permissão criada.');
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update([
            'name' => $request->validated('name'),
        ]);

        return to_route('admin.permissions.index')->with('success', 'Permissão atualizada.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return to_route('admin.permissions.index')->with('success', 'Permissão excluída.');
    }
}
