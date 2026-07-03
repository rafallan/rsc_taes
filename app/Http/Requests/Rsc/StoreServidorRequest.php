<?php

namespace App\Http\Requests\Rsc;

use App\Models\Servidor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServidorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $servidorId = Servidor::query()
            ->where('user_id', $this->user()?->id)
            ->value('id');

        return [
            'escolaridade_id' => ['required', 'integer', Rule::exists('escolaridades', 'id')],
            'nome' => ['required', 'string', 'max:255'],
            'siape' => ['required', 'string', 'max:20', Rule::unique('servidores', 'siape')->ignore($servidorId)],
            'cpf' => ['required', 'string', 'max:14', Rule::unique('servidores', 'cpf')->ignore($servidorId)],
            'email_institucional' => ['required', 'email', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'unidade_lotacao' => ['required', 'string', 'max:255'],
            'data_ingresso_cargo' => ['required', 'date'],
            'estagio_probatorio' => ['required', 'boolean'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
