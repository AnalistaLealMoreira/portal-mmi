<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Artisan;

/*
| Cria (ou promove) o primeiro Administrador do portal. Equivale ao
| `createsuperuser` + promoção de role descritos no README do portal Django.
*/
Artisan::command('portal:criar-admin {username} {--email=}', function (string $username) {
    $senha = $this->secret('Senha');
    if (! $senha || $senha !== $this->secret('Confirme a senha')) {
        $this->error('As senhas não conferem.');

        return 1;
    }

    $usuario = Usuario::firstOrNew(['username' => $username]);
    $usuario->fill([
        'email' => $this->option('email') ?? $usuario->email ?? '',
        'role' => Usuario::ADMIN,
        'is_superuser' => true,
        'is_staff' => true,
        'is_active' => true,
        'password' => $senha,
    ])->save();

    $this->info("Administrador \"{$username}\" pronto para acessar o portal.");
})->purpose('Cria ou promove um usuário a Administrador do portal');
