<?php

namespace App\Models;

use App\Support\Cidr;
use Illuminate\Database\Eloquent\Model;

class RedePermitida extends Model
{
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = null;

    protected $table = 'accounts_redepermitida';

    protected $fillable = ['rede', 'descricao', 'ativo'];

    protected $attributes = ['ativo' => true, 'descricao' => ''];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'criado_em' => 'datetime'];
    }

    public function pertence(?string $enderecoIp): bool
    {
        return Cidr::contem($this->rede, $enderecoIp);
    }

    public static function ipPermitido(?string $enderecoIp): bool
    {
        if (! $enderecoIp) {
            return false;
        }

        return static::query()->where('ativo', true)->pluck('rede')
            ->contains(fn (string $rede) => Cidr::contem($rede, $enderecoIp));
    }
}
