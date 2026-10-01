<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LinkBi extends Model
{
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'bi_links_linkbi';

    protected $fillable = ['setor_id', 'nome', 'url', 'descricao', 'ativo', 'criado_por_id'];

    protected $attributes = ['ativo' => true, 'descricao' => ''];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'criado_em' => 'datetime', 'atualizado_em' => 'datetime'];
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por_id');
    }

    public function acessos(): HasMany
    {
        return $this->hasMany(AcessoLog::class, 'link_id');
    }

    public function funcionariosLiberados(): BelongsToMany
    {
        return $this->belongsToMany(Funcionario::class, 'funcionarios_funcionario_links_liberados', 'linkbi_id', 'funcionario_id');
    }

    /**
     * Regras de visibilidade por papel:
     * - Admin: todos;
     * - Diretor / Admin de Empresa: todos os links da própria empresa;
     * - Especial: links do próprio setor + os liberados individualmente;
     * - Normal: somente os liberados individualmente.
     */
    public function scopeVisivelPara(Builder $query, Usuario $usuario): Builder
    {
        if ($usuario->isAdmin()) {
            return $query;
        }

        $funcionario = $usuario->funcionario;
        if (! $funcionario) {
            return $query->whereRaw('1 = 0');
        }

        if ($usuario->isDiretor() || $usuario->isAdminEmpresa()) {
            return $query->whereHas('setor', fn (Builder $q) => $q->where('empresa_id', $funcionario->empresa_id));
        }

        $liberado = fn (Builder $q) => $q->where('funcionarios_funcionario.id', $funcionario->id);

        if ($usuario->isEspecial()) {
            return $query->where(function (Builder $q) use ($funcionario, $liberado) {
                $q->where('bi_links_linkbi.setor_id', $funcionario->setor_id ?? 0)
                    ->orWhereHas('funcionariosLiberados', $liberado);
            });
        }

        return $query->whereHas('funcionariosLiberados', $liberado);
    }

    public function scopeDaEmpresa(Builder $query, Empresa|int $empresa): Builder
    {
        $empresaId = $empresa instanceof Empresa ? $empresa->getKey() : $empresa;

        return $query->whereHas('setor', fn (Builder $q) => $q->where('empresa_id', $empresaId));
    }

    /** Ordenação padrão do Django: empresa, setor, nome. */
    public function scopeOrdenado(Builder $query): Builder
    {
        return $query
            ->select('bi_links_linkbi.*')
            ->join('setores_setor as ord_setor', 'ord_setor.id', '=', 'bi_links_linkbi.setor_id')
            ->join('empresas_empresa as ord_empresa', 'ord_empresa.id', '=', 'ord_setor.empresa_id')
            ->orderBy('ord_empresa.nome')
            ->orderBy('ord_setor.nome')
            ->orderBy('bi_links_linkbi.nome');
    }

    public function __toString(): string
    {
        return $this->nome;
    }
}
