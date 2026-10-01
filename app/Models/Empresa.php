<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = null;

    protected $table = 'empresas_empresa';

    protected $fillable = ['nome', 'cnpj', 'ativo'];

    protected $attributes = ['ativo' => true];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'criado_em' => 'datetime'];
    }

    public function setores(): HasMany
    {
        return $this->hasMany(Setor::class, 'empresa_id');
    }

    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class, 'empresa_id');
    }

    public function getIniciaisAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->nome, 0, 2));
    }

    public function __toString(): string
    {
        return $this->nome;
    }
}
