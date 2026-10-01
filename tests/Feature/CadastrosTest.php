<?php

namespace Tests\Feature;

use App\Http\Controllers\UsuarioController;
use App\Models\AcessoLog;
use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\Setor;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class CadastrosTest extends PortalTestCase
{
    public function test_username_e_gerado_pelo_email(): void
    {
        $this->assertSame('Joao Silva', UsuarioController::usernameDoEmail('joao.silva@x.com'));
        $this->assertSame('Ana Maria Souza', UsuarioController::usernameDoEmail('Ána_maria-souza@x.com'));
        $this->assertSame('Joao2Silva', UsuarioController::usernameDoEmail('joao2silva@x.com'));
        $this->assertSame('', UsuarioController::usernameDoEmail('sem-arroba'));
    }

    public function test_admin_empresa_cadastra_usuario_na_propria_empresa_mesmo_se_postar_outra(): void
    {
        $this->actingAs($this->adminEmpresaA)->post('/usuarios/novo', [
            'username' => 'ignorado', 'first_name' => 'Maria', 'email' => 'maria.souza@teste.com',
            'password' => 'x12345678', 'role' => 'NORMAL', 'setor_id' => $this->setorA2->id,
            'empresa_id' => $this->empresaB->id, 'empresa_id_lock' => $this->empresaB->id,
        ])->assertRedirect(route('usuarios.index'));

        $usuario = Usuario::where('username', 'Maria Souza')->firstOrFail();
        $this->assertSame($this->empresaA->id, $usuario->funcionario->empresa_id);
        $this->assertTrue(Hash::check('x12345678', $usuario->password));
    }

    public function test_admin_empresa_nao_atribui_setor_de_outra_empresa(): void
    {
        $this->actingAs($this->adminEmpresaA)->post('/usuarios/novo', [
            'first_name' => 'X', 'email' => 'x@teste.com', 'password' => 'x', 'role' => 'NORMAL',
            'setor_id' => $this->setorB1->id,
        ])->assertSessionHasErrors('setor_id');
    }

    public function test_admin_global_escolhe_empresa_e_setor_deve_pertencer_a_ela(): void
    {
        $this->actingAs($this->admin)->get('/usuarios/novo')->assertSee('name="empresa_id"', false);

        $this->actingAs($this->admin)->post('/usuarios/novo', [
            'first_name' => 'X', 'email' => 'x@teste.com', 'password' => 'x', 'role' => 'NORMAL',
            'empresa_id' => $this->empresaA->id, 'setor_id' => $this->setorB1->id,
        ])->assertSessionHasErrors('setor_id');

        $this->actingAs($this->admin)->post('/usuarios/novo', [
            'first_name' => 'X', 'email' => 'x@teste.com', 'password' => 'x', 'role' => 'DIRETOR',
            'empresa_id' => $this->empresaB->id, 'setor_id' => $this->setorB1->id,
        ])->assertRedirect(route('usuarios.index'));
        $this->assertSame($this->empresaB->id, Usuario::where('username', 'X')->first()->funcionario->empresa_id);
    }

    public function test_admin_global_com_empresa_travada_pela_url(): void
    {
        $this->actingAs($this->admin)->get("/usuarios/novo?empresa_id_lock={$this->empresaB->id}")
            ->assertOk()->assertDontSee('name="empresa_id"', false)->assertSee('Cadastrar usuário em Empresa B');

        $this->actingAs($this->admin)->post('/usuarios/novo', [
            'first_name' => 'Y', 'email' => 'y@teste.com', 'password' => 'x', 'role' => 'NORMAL',
            'setor_id' => $this->setorB1->id, 'empresa_id_lock' => $this->empresaB->id,
        ])->assertRedirect(route('empresas.show', $this->empresaB));
    }

    public function test_username_duplicado_nao_permite_cadastro(): void
    {
        $this->usuario('Fulano Tal', 'NORMAL', $this->setorA1);

        $this->actingAs($this->admin)->post('/usuarios/novo', [
            'first_name' => 'X', 'email' => 'FULANO.TAL@outro.com', 'password' => 'x', 'role' => 'NORMAL',
            'empresa_id' => $this->empresaA->id, 'setor_id' => $this->setorA1->id,
        ])->assertSessionHasErrors('username');
    }

    public function test_admin_empresa_ve_e_edita_apenas_usuarios_da_propria_empresa(): void
    {
        $this->actingAs($this->adminEmpresaA)->get('/usuarios')
            ->assertSee('normal_a')->assertDontSee('normal_b');
        $this->actingAs($this->adminEmpresaA)->get("/usuarios/{$this->normalB->funcionario->id}/editar")->assertNotFound();
        $this->actingAs($this->adminEmpresaA)->post("/usuarios/{$this->normalB->funcionario->id}/excluir")->assertNotFound();
        $this->actingAs($this->admin)->get('/usuarios')->assertSee('normal_a')->assertSee('normal_b');
        $this->actingAs($this->normalA)->get('/usuarios')->assertForbidden();
    }

    public function test_edicao_troca_senha_opcionalmente_e_mantem_login(): void
    {
        $f = $this->normalA->funcionario;
        $hashAntigo = $this->normalA->password;

        $dados = ['first_name' => 'Novo', 'role' => 'ESPECIAL', 'setor_id' => $this->setorA2->id, 'username' => 'hacker'];
        $this->actingAs($this->adminEmpresaA)->post("/usuarios/{$f->id}/editar", $dados)->assertRedirect(route('usuarios.index'));

        $usuario = $this->normalA->fresh();
        $this->assertSame('normal_a', $usuario->username);
        $this->assertSame('ESPECIAL', $usuario->role);
        $this->assertSame($hashAntigo, $usuario->password);
        $this->assertSame($this->setorA2->id, $usuario->funcionario->setor_id);

        $this->actingAs($this->adminEmpresaA)->post("/usuarios/{$f->id}/editar", $dados + ['password' => 'nova-senha']);
        $this->assertTrue(Hash::check('nova-senha', $this->normalA->fresh()->password));
    }

    public function test_exclusao_de_usuario_apaga_links_criados_e_historico(): void
    {
        $linkDoNormal = $this->link($this->setorA1, 'Link do Normal');
        $linkDoNormal->update(['criado_por_id' => $this->normalA->id]);
        AcessoLog::create(['usuario_id' => $this->normalA->id, 'link_id' => $this->linkA1->id]);
        AcessoLog::create(['usuario_id' => $this->diretorA->id, 'link_id' => $linkDoNormal->id]);

        $this->actingAs($this->admin)->post("/usuarios/{$this->normalA->funcionario->id}/excluir")
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseMissing('accounts_usuario', ['id' => $this->normalA->id]);
        $this->assertDatabaseMissing('funcionarios_funcionario', ['usuario_id' => $this->normalA->id]);
        $this->assertDatabaseMissing('bi_links_linkbi', ['id' => $linkDoNormal->id]);
        $this->assertSame(0, AcessoLog::count());
    }

    public function test_setores_escopados_pela_empresa_e_exclusao_em_cascata(): void
    {
        $this->actingAs($this->admin)->get("/empresas/{$this->empresaA->id}/setores")
            ->assertSee('Financeiro')->assertDontSee('>RH<', false);
        $this->actingAs($this->admin)->get("/empresas/{$this->empresaA->id}/setores/{$this->setorB1->id}/editar")->assertNotFound();

        $this->actingAs($this->admin)->post("/empresas/{$this->empresaA->id}/setores/novo", ['nome' => 'Financeiro'])
            ->assertSessionHasErrors('nome');

        $this->actingAs($this->admin)->post("/empresas/{$this->empresaA->id}/setores/{$this->setorA1->id}/excluir")
            ->assertRedirect(route('setores.index', $this->empresaA));
        $this->assertNull(Setor::find($this->setorA1->id));
        $this->assertNull(Usuario::find($this->normalA->id));
        $this->assertDatabaseMissing('bi_links_linkbi', ['id' => $this->linkA1->id]);
    }

    public function test_empresa_status_e_protecao_contra_exclusao(): void
    {
        $this->actingAs($this->admin)->get('/empresas/nova')->assertSee('value="1" checked', false);
        $this->actingAs($this->admin)->post('/empresas/nova', ['nome' => 'C', 'cnpj' => '3'])->assertSessionHasErrors('ativo');
        $this->actingAs($this->admin)->post('/empresas/nova', ['nome' => 'C', 'cnpj' => '3', 'ativo' => '0'])->assertRedirect();
        $this->assertFalse(Empresa::where('cnpj', '3')->first()->ativo);

        $this->actingAs($this->admin)->post("/empresas/{$this->empresaA->id}/excluir")
            ->assertSessionHas('error');
        $this->assertNotNull(Empresa::find($this->empresaA->id));

        $vazia = Empresa::where('cnpj', '3')->first();
        $this->actingAs($this->admin)->post("/empresas/{$vazia->id}/excluir")->assertRedirect(route('empresas.index'));
        $this->assertNull(Empresa::find($vazia->id));
    }

    public function test_hub_da_empresa_grava_empresa_ativa_e_menu_segue_ela(): void
    {
        $this->actingAs($this->admin)->get("/empresas/{$this->empresaB->id}")->assertOk()
            ->assertSessionHas('empresa_ativa_id', $this->empresaB->id);
        $this->actingAs($this->admin)->get('/dashboard')
            ->assertSee("/empresas/{$this->empresaB->id}/setores", false);

        $this->actingAs($this->adminEmpresaA)->get("/empresas/{$this->empresaB->id}")->assertNotFound();
        $this->actingAs($this->adminEmpresaA)->get('/dashboard')
            ->assertDontSee('id="shellEmpresaToggle"', false)->assertDontSee('Empresas</span>', false);
    }

    public function test_dashboards_por_papel(): void
    {
        AcessoLog::create(['usuario_id' => $this->normalA->id, 'link_id' => $this->linkA1->id]);

        $this->actingAs($this->admin)->get('/dashboard')->assertOk()->assertSee('Empresas Cadastradas');
        foreach ([$this->adminEmpresaA, $this->diretorA, $this->normalA, $this->especialA] as $usuario) {
            $this->actingAs($usuario)->get('/dashboard')->assertOk()->assertSee('Indicadores de Alerta');
        }
        $this->actingAs($this->diretorA)->get('/dashboard')->assertSee('Financeiro');
    }

    public function test_especial_ve_setor_de_link_compartilhado_na_barra_lateral(): void
    {
        $this->especialA->funcionario->linksLiberados()->attach($this->linkA2->id);

        $this->actingAs($this->especialA)->get('/dashboard')
            ->assertSee('Abrir links de Comercial')->assertSee('Abrir links de Financeiro');
        $this->actingAs($this->normalA)->get('/dashboard')
            ->assertDontSee('Administração')->assertDontSee('Abrir links de Comercial');
    }
}
