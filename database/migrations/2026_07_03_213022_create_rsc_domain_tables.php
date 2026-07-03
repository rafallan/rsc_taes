<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('escolaridades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->unsignedSmallInteger('ordem')->unique();
            $table->timestamps();
        });

        Schema::create('niveis_rsc', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nome', 100);
            $table->foreignId('escolaridade_minima_id')->constrained('escolaridades');
            $table->decimal('pontos_minimos', 6, 2);
            $table->unsignedSmallInteger('criterios_minimos');
            $table->decimal('percentual_iq', 5, 2);
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('requisitos_rsc', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->string('nome');
            $table->text('descricao');
            $table->timestamps();
        });

        Schema::create('nivel_rsc_requisitos_obrigatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nivel_rsc_id')->constrained('niveis_rsc')->cascadeOnDelete();
            $table->foreignId('requisito_rsc_id')->constrained('requisitos_rsc')->cascadeOnDelete();
            $table->string('tipo_regra', 50)->default('AO_MENOS_UM');
            $table->timestamps();

            $table->unique(['nivel_rsc_id', 'requisito_rsc_id'], 'nivel_req_obrigatorio_unique');
        });

        Schema::create('criterios_rsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisito_rsc_id')->constrained('requisitos_rsc');
            $table->unsignedSmallInteger('item');
            $table->text('descricao');
            $table->string('unidade_medida');
            $table->decimal('pontos', 6, 2);
            $table->boolean('permite_multiplicacao')->default(true);
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();

            $table->unique(['requisito_rsc_id', 'item']);
        });

        Schema::create('criterio_rsc_variacoes_pontuacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterio_rsc_id')->constrained('criterios_rsc')->cascadeOnDelete();
            $table->string('nome', 100);
            $table->decimal('pontos', 6, 2);
            $table->timestamps();

            $table->unique(['criterio_rsc_id', 'nome'], 'criterio_variacao_nome_unique');
        });

        Schema::create('servidores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('escolaridade_id')->constrained('escolaridades');
            $table->string('nome');
            $table->string('siape', 20)->unique();
            $table->string('cpf', 14)->unique();
            $table->string('email_institucional');
            $table->string('cargo');
            $table->string('unidade_lotacao');
            $table->date('data_ingresso_cargo');
            $table->boolean('estagio_probatorio')->default(false)->index();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('solicitacoes_rsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servidor_id')->constrained('servidores');
            $table->foreignId('nivel_rsc_id')->constrained('niveis_rsc');
            $table->string('numero_protocolo', 50)->unique();
            $table->string('status', 50)->index();
            $table->dateTime('data_abertura');
            $table->dateTime('data_submissao')->nullable()->index();
            $table->decimal('saldo_pontos_anterior', 8, 2)->default(0);
            $table->decimal('pontos_declarados', 8, 2)->default(0);
            $table->unsignedSmallInteger('criterios_declarados')->default(0);
            $table->longText('memorial')->nullable();
            $table->boolean('declaracao_veracidade')->default(false);
            $table->boolean('declaracao_nao_reutilizacao')->default(false);
            $table->timestamps();

            $table->index(['servidor_id', 'status']);
        });

        Schema::create('solicitacao_rsc_criterios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_rsc_id')->constrained('solicitacoes_rsc')->cascadeOnDelete();
            $table->foreignId('criterio_rsc_id')->constrained('criterios_rsc');
            $table->foreignId('variacao_pontuacao_id')->nullable()->constrained('criterio_rsc_variacoes_pontuacao')->nullOnDelete();
            $table->string('titulo_atividade');
            $table->text('descricao_atividade');
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->decimal('quantidade', 8, 2);
            $table->decimal('pontos_unitarios', 6, 2);
            $table->decimal('pontos_calculados', 8, 2);
            $table->boolean('atividade_exercicio_cargo')->default(true);
            $table->boolean('atividade_ordinaria_cargo')->default(false);
            $table->text('justificativa_relevancia');
            $table->boolean('usado_em_concessao_anterior')->default(false);
            $table->string('status_avaliacao', 50)->nullable();
            $table->decimal('pontos_aceitos', 8, 2)->nullable();
            $table->text('parecer_avaliador')->nullable();
            $table->timestamps();

            $table->index(['solicitacao_rsc_id', 'criterio_rsc_id'], 'solicitacao_criterio_index');
        });

        Schema::create('documentos_comprobatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_rsc_criterio_id')->constrained('solicitacao_rsc_criterios')->cascadeOnDelete();
            $table->string('tipo_documento', 100);
            $table->string('nome_original');
            $table->string('caminho_arquivo', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamanho');
            $table->text('observacao')->nullable();
            $table->boolean('validado')->nullable();
            $table->text('parecer')->nullable();
            $table->timestamps();
        });

        Schema::create('comissoes_rsc', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->date('data_instituicao');
            $table->string('ato_instituicao');
            $table->boolean('ativa')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('membros_comissao_rsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comissao_rsc_id')->constrained('comissoes_rsc')->cascadeOnDelete();
            $table->foreignId('servidor_id')->constrained('servidores');
            $table->string('tipo', 50);
            $table->string('origem_indicacao', 100);
            $table->date('data_inicio_mandato');
            $table->date('data_fim_mandato');
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('avaliacoes_rsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_rsc_id')->constrained('solicitacoes_rsc');
            $table->foreignId('comissao_rsc_id')->constrained('comissoes_rsc');
            $table->foreignId('avaliador_id')->nullable()->constrained('membros_comissao_rsc')->nullOnDelete();
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim')->nullable();
            $table->decimal('pontos_declarados', 8, 2);
            $table->decimal('pontos_reconhecidos', 8, 2)->nullable();
            $table->unsignedSmallInteger('criterios_reconhecidos')->nullable();
            $table->string('resultado', 50)->nullable();
            $table->longText('parecer_final')->nullable();
            $table->timestamps();
        });

        Schema::create('avaliacao_rsc_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avaliacao_rsc_id')->constrained('avaliacoes_rsc')->cascadeOnDelete();
            $table->foreignId('solicitacao_rsc_criterio_id')->constrained('solicitacao_rsc_criterios');
            $table->string('status', 50);
            $table->decimal('pontos_solicitados', 8, 2);
            $table->decimal('pontos_aceitos', 8, 2);
            $table->text('motivo_recusa')->nullable();
            $table->text('fundamentacao');
            $table->timestamps();
        });

        Schema::create('concessoes_rsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_rsc_id')->unique()->constrained('solicitacoes_rsc');
            $table->foreignId('servidor_id')->constrained('servidores');
            $table->foreignId('nivel_rsc_id')->constrained('niveis_rsc');
            $table->date('data_deferimento');
            $table->date('data_efeito_financeiro');
            $table->decimal('pontos_utilizados', 8, 2);
            $table->decimal('saldo_pontos', 8, 2);
            $table->string('ato_concessao')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('recursos_rsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_rsc_id')->constrained('solicitacoes_rsc');
            $table->foreignId('servidor_id')->constrained('servidores');
            $table->dateTime('data_recurso');
            $table->longText('fundamentacao');
            $table->string('status', 50)->index();
            $table->longText('decisao')->nullable();
            $table->dateTime('data_decisao')->nullable();
            $table->timestamps();
        });

        Schema::create('historico_solicitacoes_rsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_rsc_id')->constrained('solicitacoes_rsc')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('status_anterior', 50)->nullable();
            $table->string('status_novo', 50);
            $table->text('descricao')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_solicitacoes_rsc');
        Schema::dropIfExists('recursos_rsc');
        Schema::dropIfExists('concessoes_rsc');
        Schema::dropIfExists('avaliacao_rsc_itens');
        Schema::dropIfExists('avaliacoes_rsc');
        Schema::dropIfExists('membros_comissao_rsc');
        Schema::dropIfExists('comissoes_rsc');
        Schema::dropIfExists('documentos_comprobatorios');
        Schema::dropIfExists('solicitacao_rsc_criterios');
        Schema::dropIfExists('solicitacoes_rsc');
        Schema::dropIfExists('servidores');
        Schema::dropIfExists('criterio_rsc_variacoes_pontuacao');
        Schema::dropIfExists('criterios_rsc');
        Schema::dropIfExists('nivel_rsc_requisitos_obrigatorios');
        Schema::dropIfExists('requisitos_rsc');
        Schema::dropIfExists('niveis_rsc');
        Schema::dropIfExists('escolaridades');
    }
};
