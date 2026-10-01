<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Vínculo do usuário com empresa e setor (tabela `funcionarios_funcionario`).
 */
class Funcionario extends Model
{
    protected $table = 'funcionarios_funcionario';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'empresa_id', 'setor_id'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_id');
    }

    public function linksLiberados(): BelongsToMany
    {
        return $this->belongsToMany(LinkBi::class, 'funcionarios_funcionario_links_liberados', 'funcionario_id', 'linkbi_id');
    }

    /** Ordenação padrão do Django: empresa, depois primeiro nome. */
    public function scopeOrdenado(Builder $query): Builder
    {
        return $query
            ->select('funcionarios_funcionario.*')
            ->join('empresas_empresa as ord_empresa', 'ord_empresa.id', '=', 'funcionarios_funcionario.empresa_id')
            ->join('accounts_usuario as ord_usuario', 'ord_usuario.id', '=', 'funcionarios_funcionario.usuario_id')
            ->orderBy('ord_empresa.nome')
            ->orderBy('ord_usuario.first_name');
    }

    /** Admin global gerencia todos; Admin de Empresa só os da própria empresa. */
    public function scopeGerenciaveisPor(Builder $query, Usuario $usuario): Builder
    {
        if ($usuario->isAdmin()) {
            return $query;
        }

        return $query->where('funcionarios_funcionario.empresa_id', $usuario->empresaId() ?? 0);
    }

    /** Rótulo usado na lista de liberação de links: "Nome (Setor)". */
    public function getRotuloComSetorAttribute(): string
    {
        return $this->usuario->nome_exibicao.' ('.($this->setor?->nome ?? 'sem setor').')';
    }
}
