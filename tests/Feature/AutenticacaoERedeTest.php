<?php

namespace Tests\Feature;

use App\Models\RedePermitida;
use App\Models\Usuario;

class AutenticacaoERedeTest extends PortalTestCase
{
    public function test_login_por_usuario_ou_email_sem_diferenciar_maiusculas(): void
    {
        $this->post('/accounts/login', ['username' => 'diretor_a', 'password' => 'senha12345'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->diretorA);

        $this->post('/accounts/logout');
        $this->post('/accounts/login', ['username' => 'DIRETOR_A@TESTE.COM', 'password' => 'senha12345'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->diretorA);
    }

    public function test_login_com_senha_errada_falha(): void
    {
        $this->post('/accounts/login', ['username' => 'diretor_a', 'password' => 'errada'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_aceita_hash_gerado_pelo_django(): void
    {
        // Hash real gerado pelo Django 5.2 para a senha "Senha@123".
        Usuario::whereKey($this->diretorA->id)->update([
            'password' => 'pbkdf2_sha256$1000000$wK5faUz1FM2sdPLAwpHxQY$UQDAnVD5iKyuLfyi9IaszGUZ3UiJel9ErNRtyghz5Mc=',
        ]);

        $this->post('/accounts/login', ['username' => 'diretor_a', 'password' => 'Senha@123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->diretorA);
    }

    public function test_usuario_inativo_nao_entra(): void
    {
        Usuario::whereKey($this->diretorA->id)->update(['is_active' => false]);

        $this->post('/accounts/login', ['username' => 'diretor_a', 'password' => 'senha12345']);
        $this->assertGuest();
    }

    public function test_formulario_de_rede_normaliza_cidr(): void
    {
        $this->actingAs($this->admin)->post('/accounts/redes/nova', ['rede' => '192.168.1.10/24', 'ativo' => '1'])
            ->assertRedirect(route('redes.index'));
        $this->assertDatabaseHas('accounts_redepermitida', ['rede' => '192.168.1.0/24']);

        $this->actingAs($this->admin)->post('/accounts/redes/nova', ['rede' => '2001:db8::10'])
            ->assertRedirect(route('redes.index'));
        $this->assertDatabaseHas('accounts_redepermitida', ['rede' => '2001:db8::10/128', 'ativo' => false]);

        $this->actingAs($this->admin)->post('/accounts/redes/nova', ['rede' => 'nao-e-ip'])
            ->assertSessionHasErrors('rede');
    }

    public function test_usuario_normal_e_bloqueado_fora_da_rede(): void
    {
        RedePermitida::query()->update(['rede' => '10.0.0.0/8']);

        $this->actingAs($this->normalA)->get('/dashboard')
            ->assertForbidden()
            ->assertSee('Acesso restrito à rede da empresa');
    }

    public function test_usuario_normal_e_liberado_na_rede(): void
    {
        $this->actingAs($this->normalA)->get('/dashboard')->assertOk();
    }

    public function test_sem_rede_ativa_usuario_normal_fica_bloqueado(): void
    {
        RedePermitida::query()->update(['ativo' => false]);

        $this->actingAs($this->normalA)->get('/dashboard')->assertForbidden();
    }

    public function test_diretor_admin_e_especial_nao_dependem_de_rede(): void
    {
        RedePermitida::query()->delete();

        foreach ([$this->diretorA, $this->admin, $this->especialA] as $usuario) {
            $this->actingAs($usuario)->get('/dashboard')->assertOk();
        }
    }

    public function test_crud_de_redes_so_permite_admin(): void
    {
        $this->actingAs($this->diretorA)->get('/accounts/redes')->assertForbidden();
        $this->actingAs($this->admin)->get('/accounts/redes')->assertOk()->assertSee('127.0.0.1/32');
    }
}
