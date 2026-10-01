<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\LinkBi;
use App\Models\RedePermitida;
use App\Models\Setor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cenário base (o mesmo dos testes do portal Django): duas empresas, setores,
 * um usuário de cada papel e links em setores diferentes.
 */
abstract class PortalTestCase extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresaA;
    protected Empresa $empresaB;
    protected Setor $setorA1;
    protected Setor $setorA2;
    protected Setor $setorB1;
    protected Usuario $admin;
    protected Usuario $normalA;
    protected Usuario $diretorA;
    protected Usuario $especialA;
    protected Usuario $adminEmpresaA;
    protected Usuario $normalB;
    protected LinkBi $linkA1;
    protected LinkBi $linkA2;
    protected LinkBi $linkB1;

    protected function setUp(): void
    {
        parent::setUp();

        RedePermitida::create(['rede' => '127.0.0.1/32', 'descricao' => 'Rede de testes']);
        $this->empresaA = Empresa::create(['nome' => 'Empresa A', 'cnpj' => '11.111.111/0001-11']);
        $this->empresaB = Empresa::create(['nome' => 'Empresa B', 'cnpj' => '22.222.222/0001-22']);

        $this->setorA1 = Setor::create(['empresa_id' => $this->empresaA->id, 'nome' => 'Financeiro']);
        $this->setorA2 = Setor::create(['empresa_id' => $this->empresaA->id, 'nome' => 'Comercial']);
        $this->setorB1 = Setor::create(['empresa_id' => $this->empresaB->id, 'nome' => 'RH']);

        $this->admin = $this->usuario('admin_teste', Usuario::ADMIN);
        $this->normalA = $this->usuario('normal_a', Usuario::NORMAL, $this->setorA1);
        $this->diretorA = $this->usuario('diretor_a', Usuario::DIRETOR, $this->setorA1);
        $this->especialA = $this->usuario('especial_a', Usuario::ESPECIAL, $this->setorA1);
        $this->adminEmpresaA = $this->usuario('admin_empresa_a', Usuario::ADMIN_EMPRESA, $this->setorA1);
        $this->normalB = $this->usuario('normal_b', Usuario::NORMAL, $this->setorB1);

        $this->linkA1 = $this->link($this->setorA1, 'Dashboard Financeiro');
        $this->linkA2 = $this->link($this->setorA2, 'Dashboard Comercial');
        $this->linkB1 = $this->link($this->setorB1, 'Dashboard RH');

        $this->normalA->funcionario->linksLiberados()->attach($this->linkA1->id);
        $this->normalB->funcionario->linksLiberados()->attach($this->linkB1->id);
    }

    protected function usuario(string $username, string $role, ?Setor $setor = null): Usuario
    {
        $usuario = Usuario::create([
            'username' => $username,
            'email' => "$username@teste.com",
            'role' => $role,
            'password' => 'senha12345',
        ]);
        if ($setor) {
            Funcionario::create(['usuario_id' => $usuario->id, 'empresa_id' => $setor->empresa_id, 'setor_id' => $setor->id]);
        }

        return $usuario->fresh();
    }

    protected function link(Setor $setor, string $nome): LinkBi
    {
        return LinkBi::create([
            'setor_id' => $setor->id,
            'nome' => $nome,
            'url' => 'https://example.com/'.str_replace(' ', '-', strtolower($nome)),
            'criado_por_id' => $this->admin->id,
        ]);
    }
}
