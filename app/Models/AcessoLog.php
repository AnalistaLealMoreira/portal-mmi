<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro append-only de acessos a links de BI. Nunca deve ser editado.
 */
class AcessoLog extends Model
{
    public const CREATED_AT = 'acessado_em';
    public const UPDATED_AT = null;

    protected $table = 'auditoria_acessolog';

    protected $fillable = ['usuario_id', 'link_id', 'ip_address', 'user_agent'];

    protected $attributes = ['user_agent' => ''];

    protected function casts(): array
    {
        return ['acessado_em' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(LinkBi::class, 'link_id');
    }

    /** Admin vê tudo; Diretor / Admin de Empresa veem os acessos da própria empresa. */
    public function scopeVisivelPara(Builder $query, Usuario $usuario): Builder
    {
        if ($usuario->isAdmin()) {
            return $query;
        }

        $empresaId = $usuario->empresaId();
        if (($usuario->isDiretor() || $usuario->isAdminEmpresa()) && $empresaId) {
            return $query->whereHas('usuario.funcionario', fn (Builder $q) => $q->where('empresa_id', $empresaId));
        }

        return $query->whereRaw('1 = 0');
    }
}
