<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setor extends Model
{
    protected $table = 'setores_setor';

    public $timestamps = false;

    protected $fillable = ['nome', 'empresa_id'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function linksBi(): HasMany
    {
        return $this->hasMany(LinkBi::class, 'setor_id');
    }

    public function funcionarios(): HasMany
    {
        return $this->hasMany(Funcionario::class, 'setor_id');
    }

    public function scopeDaEmpresa(Builder $query, Empresa|int $empresa): Builder
    {
        return $query->where('empresa_id', $empresa instanceof Empresa ? $empresa->getKey() : $empresa);
    }

    public function __toString(): string
    {
        return $this->nome;
    }
}
