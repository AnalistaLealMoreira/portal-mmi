<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema do portal, idêntico ao criado pelas migrações do Django.
 *
 * Cada tabela só é criada se ainda não existir: num banco que já veio do
 * portal Django (SQLite de desenvolvimento ou SQL Server de produção) esta
 * migração não altera nada e apenas fica registrada como executada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->criar('accounts_usuario', function (Blueprint $table) {
            $table->id();
            $table->string('password', 128);
            $table->dateTime('last_login', 6)->nullable();
            $table->boolean('is_superuser')->default(false);
            $table->string('username', 150)->unique();
            $table->string('first_name', 150)->default('');
            $table->string('last_name', 150)->default('');
            $table->string('email', 254)->default('');
            $table->boolean('is_staff')->default(false);
            $table->boolean('is_active')->default(true);
            $table->dateTime('date_joined', 6);
            $table->dateTime('last_seen', 6)->nullable();
            $table->string('role', 20)->default('NORMAL');
        });

        $this->criar('accounts_redepermitida', function (Blueprint $table) {
            $table->id();
            $table->string('rede', 43)->unique();
            $table->string('descricao', 120)->default('');
            $table->boolean('ativo')->default(true);
            $table->dateTime('criado_em', 6);
        });

        $this->criar('empresas_empresa', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->string('cnpj', 18)->unique();
            $table->boolean('ativo')->default(true);
            $table->dateTime('criado_em', 6);
        });

        $this->criar('setores_setor', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->foreignId('empresa_id')->constrained('empresas_empresa');
            $table->unique(['empresa_id', 'nome'], 'setor_unico_por_empresa');
        });

        $this->criar('bi_links_linkbi', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->string('url', 500);
            $table->text('descricao');
            $table->boolean('ativo')->default(true);
            $table->dateTime('criado_em', 6);
            $table->dateTime('atualizado_em', 6);
            $table->foreignId('criado_por_id')->constrained('accounts_usuario');
            $table->foreignId('setor_id')->constrained('setores_setor');
        });

        $this->criar('funcionarios_funcionario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas_empresa');
            $table->foreignId('usuario_id')->unique()->constrained('accounts_usuario');
            $table->foreignId('setor_id')->nullable()->constrained('setores_setor');
        });

        $this->criar('funcionarios_funcionario_links_liberados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios_funcionario');
            $table->foreignId('linkbi_id')->constrained('bi_links_linkbi');
            $table->unique(['funcionario_id', 'linkbi_id']);
        });

        $this->criar('auditoria_acessolog', function (Blueprint $table) {
            $table->id();
            $table->dateTime('acessado_em', 6);
            $table->string('ip_address', 39)->nullable();
            $table->string('user_agent', 500)->default('');
            $table->foreignId('link_id')->constrained('bi_links_linkbi');
            $table->foreignId('usuario_id')->constrained('accounts_usuario');
            $table->index(['usuario_id', 'acessado_em'], 'auditoria_a_usuario_089373_idx');
        });
    }

    public function down(): void
    {
        // Intencionalmente vazio: estas tabelas guardam os dados de produção
        // (inclusive auditoria) e são compartilhadas com o portal Django.
    }

    private function criar(string $tabela, Closure $definicao): void
    {
        if (! Schema::hasTable($tabela)) {
            Schema::create($tabela, $definicao);
        }
    }
};
