<?php

namespace Tests\Feature;

use App\Models\AcessoLog;
use App\Models\LinkBi;

class LinksEAuditoriaTest extends PortalTestCase
{
    public function test_normal_ve_apenas_links_liberados(): void
    {
        $this->actingAs($this->normalA)->get('/links')
            ->assertOk()
            ->assertSee('Dashboard Financeiro')
            ->assertDontSee('Dashboard Comercial')
            ->assertDontSee('Dashboard RH');
    }

    public function test_relatorios_mostram_acessados_recentemente_apenas_apos_acesso(): void
    {
        $this->actingAs($this->normalA)->get('/links')->assertDontSee('Acessados recentemente');

        $this->actingAs($this->normalA)->get("/links/{$this->linkA1->id}/acessar")->assertOk();

        $this->actingAs($this->normalA)->get('/links')
            ->assertOk()
            ->assertSee('Acessados recentemente');
    }

    public function test_normal_nao_acessa_link_de_outro_setor_nem_de_outra_empresa(): void
    {
        $this->actingAs($this->normalA)->get("/links/{$this->linkA2->id}/acessar")->assertNotFound();
        $this->actingAs($this->normalA)->get("/links/{$this->linkB1->id}/acessar")->assertNotFound();
    }

    public function test_acesso_permitido_gera_log_e_mostra_iframe_com_marca_dagua(): void
    {
        $this->actingAs($this->normalA)->get("/links/{$this->linkA1->id}/acessar")
            ->assertOk()
            ->assertSee('<iframe src="https://example.com/dashboard-financeiro"', false)
            ->assertSee('Proibido o compartilhamento');

        $this->assertDatabaseHas('auditoria_acessolog', [
            'usuario_id' => $this->normalA->id, 'link_id' => $this->linkA1->id, 'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_diretor_e_admin_empresa_nao_recebem_marca_dagua(): void
    {
        foreach ([$this->diretorA, $this->adminEmpresaA] as $usuario) {
            $this->actingAs($usuario)->get("/links/{$this->linkA1->id}/acessar")
                ->assertOk()
                ->assertDontSee('Proibido o compartilhamento');
        }
    }

    public function test_especial_ve_links_do_proprio_setor_e_os_compartilhados(): void
    {
        $this->actingAs($this->especialA)->get('/links')
            ->assertSee('Dashboard Financeiro')
            ->assertDontSee('Dashboard Comercial');
        $this->actingAs($this->especialA)->get("/links/{$this->linkA2->id}/acessar")->assertNotFound();

        $this->especialA->funcionario->linksLiberados()->attach($this->linkA2->id);
        $this->actingAs($this->especialA)->get("/links/{$this->linkA2->id}/acessar")->assertOk();
        $this->actingAs($this->especialA)->get("/links/{$this->linkB1->id}/acessar")->assertNotFound();
    }

    public function test_diretor_e_admin_empresa_veem_todos_os_links_da_propria_empresa(): void
    {
        foreach ([$this->diretorA, $this->adminEmpresaA] as $usuario) {
            $this->actingAs($usuario)->get('/links')
                ->assertSee('Dashboard Financeiro')
                ->assertSee('Dashboard Comercial')
                ->assertDontSee('Dashboard RH');
            $this->actingAs($usuario)->get("/links/{$this->linkB1->id}/acessar")->assertNotFound();
        }
    }

    public function test_admin_ve_tudo(): void
    {
        $this->actingAs($this->admin)->get('/links')
            ->assertSee('Dashboard Financeiro')->assertSee('Dashboard Comercial')->assertSee('Dashboard RH');
    }

    public function test_auditoria_diretor_ve_apenas_a_propria_empresa_e_normal_nao_acessa(): void
    {
        AcessoLog::create(['usuario_id' => $this->normalA->id, 'link_id' => $this->linkA1->id]);
        AcessoLog::create(['usuario_id' => $this->normalB->id, 'link_id' => $this->linkB1->id]);

        $this->actingAs($this->diretorA)->get('/auditoria')
            ->assertOk()->assertSee('Dashboard Financeiro')->assertDontSee('Dashboard RH');
        $this->actingAs($this->admin)->get('/auditoria')
            ->assertSee('Dashboard Financeiro')->assertSee('Dashboard RH');
        $this->actingAs($this->normalA)->get('/auditoria')->assertForbidden();
    }

    public function test_gerenciador_lista_apenas_links_da_empresa_da_url(): void
    {
        $this->actingAs($this->admin)->get("/empresas/{$this->empresaA->id}/links")
            ->assertOk()->assertSee('Dashboard Financeiro')->assertDontSee('Dashboard RH');
    }

    public function test_nao_edita_link_de_outra_empresa_via_url_forjada(): void
    {
        $this->actingAs($this->admin)->get("/empresas/{$this->empresaA->id}/links/{$this->linkB1->id}/editar")->assertNotFound();
        $this->actingAs($this->admin)->get('/empresas/999/links')->assertNotFound();
    }

    public function test_admin_empresa_so_gerencia_a_propria_empresa_e_normal_nao_gerencia(): void
    {
        $this->actingAs($this->adminEmpresaA)->get("/empresas/{$this->empresaA->id}/links")->assertOk();
        $this->actingAs($this->adminEmpresaA)->get("/empresas/{$this->empresaB->id}/links")->assertNotFound();
        $this->actingAs($this->normalA)->get("/empresas/{$this->empresaA->id}/links")->assertForbidden();
    }

    public function test_criar_link_liberando_varios_usuarios_e_registrando_autor(): void
    {
        $outroNormal = $this->usuario('normal_a2', 'NORMAL', $this->setorA1);

        $this->actingAs($this->adminEmpresaA)->post("/empresas/{$this->empresaA->id}/links/novo", [
            'setor_id' => $this->setorA1->id,
            'nome' => 'Novo Painel',
            'url' => 'https://example.com/novo',
            'ativo' => '1',
            'usuarios_liberados' => [$this->normalA->funcionario->id, $outroNormal->funcionario->id],
        ])->assertRedirect(route('links-admin.index', $this->empresaA));

        $link = LinkBi::where('nome', 'Novo Painel')->firstOrFail();
        $this->assertSame($this->adminEmpresaA->id, $link->criado_por_id);
        $this->assertEqualsCanonicalizing(
            [$this->normalA->funcionario->id, $outroNormal->funcionario->id],
            $link->funcionariosLiberados()->pluck('funcionarios_funcionario.id')->all()
        );
    }

    public function test_nao_libera_link_para_usuario_de_outro_setor_exceto_especial(): void
    {
        $dados = ['setor_id' => $this->setorA2->id, 'nome' => 'X', 'url' => 'https://example.com/x', 'ativo' => '1'];

        $this->actingAs($this->admin)->post("/empresas/{$this->empresaA->id}/links/novo",
            $dados + ['usuarios_liberados' => [$this->normalA->funcionario->id]])
            ->assertSessionHasErrors('usuarios_liberados');

        $this->actingAs($this->admin)->post("/empresas/{$this->empresaA->id}/links/novo",
            $dados + ['usuarios_liberados' => [$this->especialA->funcionario->id]])
            ->assertSessionHasNoErrors();
    }

    public function test_form_restringe_setor_a_empresa_da_url(): void
    {
        $this->actingAs($this->admin)->post("/empresas/{$this->empresaA->id}/links/novo", [
            'setor_id' => $this->setorB1->id, 'nome' => 'X', 'url' => 'https://example.com/x', 'ativo' => '1',
        ])->assertSessionHasErrors('setor_id');
    }

    public function test_exclusao_de_link_apaga_historico_em_cascata(): void
    {
        AcessoLog::create(['usuario_id' => $this->normalA->id, 'link_id' => $this->linkA1->id]);

        $this->actingAs($this->admin)->post("/empresas/{$this->empresaA->id}/links/{$this->linkA1->id}/excluir")
            ->assertRedirect(route('links-admin.index', $this->empresaA));

        $this->assertDatabaseMissing('bi_links_linkbi', ['id' => $this->linkA1->id]);
        $this->assertDatabaseMissing('auditoria_acessolog', ['link_id' => $this->linkA1->id]);
    }

    public function test_buscas_filtram_usuarios_links_e_auditoria(): void
    {
        $this->actingAs($this->admin)->get('/usuarios?q=normal_a')
            ->assertOk()->assertSee('normal_a')->assertDontSee('normal_b');

        $this->actingAs($this->admin)->get("/empresas/{$this->empresaA->id}/links?q=Comercial")
            ->assertOk()->assertSee('Dashboard Comercial')->assertDontSee('Dashboard Financeiro');

        $this->actingAs($this->normalA)->get("/links/{$this->linkA1->id}/acessar");
        $this->actingAs($this->normalB)->get("/links/{$this->linkB1->id}/acessar");

        $this->actingAs($this->admin)->get('/auditoria?q=normal_b')
            ->assertOk()->assertSee('Dashboard RH')->assertDontSee('Dashboard Financeiro');
        $this->actingAs($this->admin)->get('/auditoria?de=2000-01-01&ate=2000-01-02')
            ->assertOk()->assertSee('Nenhum acesso encontrado');
    }

    public function test_listas_trazem_confirmacao_de_exclusao_com_contagem_da_cascata(): void
    {
        $this->actingAs($this->admin)->get("/empresas/{$this->empresaA->id}/setores")
            ->assertOk()
            ->assertSee('js-excluir', false)
            ->assertSee('id="modalExcluir"', false)
            ->assertSee('1 link de BI');
    }
}
