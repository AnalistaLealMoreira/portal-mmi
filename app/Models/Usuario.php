<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Usuário do portal (tabela `accounts_usuario`, herdada do Django).
 */
class Usuario extends Authenticatable
{
    public const ADMIN = 'ADMIN';
    public const ADMIN_EMPRESA = 'ADMIN_EMPRESA';
    public const DIRETOR = 'DIRETOR';
    public const ESPECIAL = 'ESPECIAL';
    public const NORMAL = 'NORMAL';

    public const ROLES = [
        self::ADMIN => 'Administrador',
        self::ADMIN_EMPRESA => 'Admin da Empresa',
        self::DIRETOR => 'Diretor',
        self::ESPECIAL => 'Usuário Especial',
        self::NORMAL => 'Usuário Normal',
    ];

    /** Níveis que Admin / Admin de Empresa podem atribuir pelo cadastro. */
    public const ROLES_GERENCIAVEIS = [
        self::NORMAL => 'Usuário Normal',
        self::ESPECIAL => 'Usuário Especial',
        self::DIRETOR => 'Diretor',
        self::ADMIN_EMPRESA => 'Admin da Empresa',
    ];

    protected $table = 'accounts_usuario';

    public $timestamps = false;

    /** A tabela do Django não tem `remember_token`; "manter conectado" é feito pela sessão. */
    protected $rememberTokenName = '';

    protected $fillable = [
        'username', 'first_name', 'last_name', 'email', 'role', 'password',
        'is_superuser', 'is_staff', 'is_active', 'date_joined',
    ];

    protected $hidden = ['password'];

    protected $attributes = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'role' => self::NORMAL,
        'is_superuser' => false,
        'is_staff' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'last_seen' => 'datetime',
            'date_joined' => 'datetime',
            'is_superuser' => 'boolean',
            'is_staff' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Usuario $usuario) {
            $usuario->date_joined ??= now();
        });
    }

    public function funcionario(): HasOne
    {
        return $this->hasOne(Funcionario::class, 'usuario_id');
    }

    public function linksCriados(): HasMany
    {
        return $this->hasMany(LinkBi::class, 'criado_por_id');
    }

    public function acessos(): HasMany
    {
        return $this->hasMany(AcessoLog::class, 'usuario_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    public function isAdminEmpresa(): bool
    {
        return $this->role === self::ADMIN_EMPRESA;
    }

    public function isDiretor(): bool
    {
        return $this->role === self::DIRETOR;
    }

    public function isEspecial(): bool
    {
        return $this->role === self::ESPECIAL;
    }

    public function isNormal(): bool
    {
        return $this->role === self::NORMAL;
    }

    public function temPapel(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Empresa do funcionário vinculado (null para quem não tem vínculo, ex.: Admin global). */
    public function empresaId(): ?int
    {
        return $this->funcionario?->empresa_id;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getNomeCompletoAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Equivalente ao `__str__` do Django: nome completo ou login. */
    public function getNomeExibicaoAttribute(): string
    {
        return $this->nome_completo ?: $this->username;
    }

    public function getIniciaisAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->username, 0, 2));
    }

    public function __toString(): string
    {
        return $this->nome_exibicao;
    }
}
