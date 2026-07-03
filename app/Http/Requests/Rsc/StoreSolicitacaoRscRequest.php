<?php

namespace App\Http\Requests\Rsc;

use App\Models\CriterioRsc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSolicitacaoRscRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->servidor !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nivel_rsc_id' => ['required', 'integer', Rule::exists('niveis_rsc', 'id')->where('ativo', true)],
            'intent' => ['required', Rule::in(['draft', 'submit'])],
            'saldo_pontos_anterior' => ['nullable', 'numeric', 'min:0'],
            'memorial' => ['nullable', 'string'],
            'declaracao_veracidade' => ['required', 'boolean'],
            'declaracao_nao_reutilizacao' => ['required', 'boolean'],
            'atividades' => ['required', 'array', 'min:1'],
            'atividades.*.criterio_rsc_id' => ['required', 'integer', Rule::exists('criterios_rsc', 'id')->where('ativo', true)],
            'atividades.*.variacao_pontuacao_id' => ['nullable', 'integer', Rule::exists('criterio_rsc_variacoes_pontuacao', 'id')],
            'atividades.*.titulo_atividade' => ['required', 'string', 'max:255'],
            'atividades.*.descricao_atividade' => ['required', 'string'],
            'atividades.*.data_inicio' => ['nullable', 'date'],
            'atividades.*.data_fim' => ['nullable', 'date'],
            'atividades.*.quantidade' => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'atividades.*.atividade_exercicio_cargo' => ['required', 'boolean'],
            'atividades.*.atividade_ordinaria_cargo' => ['required', 'boolean'],
            'atividades.*.justificativa_relevancia' => ['required', 'string'],
            'atividades.*.usado_em_concessao_anterior' => ['required', 'boolean'],
            'atividades.*.tipo_documento' => ['required', 'string', 'max:100'],
            'atividades.*.observacao_documento' => ['nullable', 'string'],
            'atividades.*.documentos' => ['nullable', 'array'],
            'atividades.*.documentos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $atividades = $this->input('atividades', []);

                if (! is_array($atividades)) {
                    return;
                }

                foreach ($atividades as $index => $atividade) {
                    if (! is_array($atividade) || ! is_numeric($atividade['criterio_rsc_id'] ?? null)) {
                        continue;
                    }

                    $criterio = CriterioRsc::query()
                        ->with('variacoesPontuacao')
                        ->find((int) $atividade['criterio_rsc_id']);

                    if (! $criterio) {
                        continue;
                    }

                    if ($criterio->variacoesPontuacao->isNotEmpty() && blank($atividade['variacao_pontuacao_id'] ?? null)) {
                        $validator->errors()->add("atividades.{$index}.variacao_pontuacao_id", 'Selecione a variação de pontuação deste critério.');
                    }

                    if (! blank($atividade['variacao_pontuacao_id'] ?? null) && $criterio->variacoesPontuacao->doesntContain('id', (int) $atividade['variacao_pontuacao_id'])) {
                        $validator->errors()->add("atividades.{$index}.variacao_pontuacao_id", 'A variação selecionada não pertence ao critério informado.');
                    }
                }
            },
        ];
    }
}
