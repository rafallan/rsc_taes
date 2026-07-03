<?php

namespace App\Http\Controllers\Rsc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rsc\StoreServidorRequest;
use App\Models\Escolaridade;
use App\Models\Servidor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('rsc/Profile', [
            'servidor' => auth()->user()?->servidor?->load('escolaridade'),
            'escolaridades' => Escolaridade::query()->orderBy('ordem')->get(['id', 'nome', 'ordem']),
        ]);
    }

    public function update(StoreServidorRequest $request): RedirectResponse
    {
        Servidor::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                ...$request->validated(),
                'user_id' => $request->user()->id,
                'ativo' => true,
            ],
        );

        return to_route('rsc.solicitacoes.index')->with('success', 'Perfil funcional salvo.');
    }
}
