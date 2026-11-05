<?php

use App\Models\CriancaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes de fluxo (rotas, autenticação e CRUDs).
 *
 * @internal
 */
final class ControllersTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    protected function loginComoAdmin(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Admin Teste',
            'usuario_email' => 'admin@teste.com',
            'perfil'        => 'admin',
        ]);
    }

    public function testRaizRedirecionaParaDashboard(): void
    {
        $result = $this->get('/');

        $result->assertRedirectTo('/dashboard');
    }

    public function testDashboardRedirecionaSemLogin(): void
    {
        $result = $this->get('dashboard');

        $result->assertRedirectTo('/login');
    }

    public function testLoginComCredenciaisValidas(): void
    {
        $result = $this->post('login/autenticar', [
            'email' => 'admin@teste.com',
            'senha' => 'admin123',
        ]);

        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('logado');
    }

    public function testLoginComSenhaInvalida(): void
    {
        $result = $this->post('login/autenticar', [
            'email' => 'admin@teste.com',
            'senha' => 'senha-errada',
        ]);

        $result->assertSessionHas('erro');
    }

    public function testDashboardCarregaDados(): void
    {
        $this->loginComoAdmin();

        $result = $this->get('dashboard');

        $result->assertStatus(200);
        $result->assertSee('Dashboard');
    }

    public function testPaginaCriancasProtegida(): void
    {
        $result = $this->get('criancas');

        $result->assertRedirectTo('/login');
    }

    public function testPaginaCriancasCarrega(): void
    {
        $this->loginComoAdmin();

        $result = $this->get('criancas');

        $result->assertStatus(200);
        $result->assertSee('Crianças Assistidas');
    }

    public function testCriarCrianca(): void
    {
        $this->loginComoAdmin();

        $result = $this->post('criancas', [
            'nome'   => 'Criança Teste',
            'status' => 'ativo',
        ]);

        $result->assertRedirectTo('/criancas');
        $this->assertSame(3, (new CriancaModel())->countAllResults());
    }

    public function testCriarCriancaComNomeInvalido(): void
    {
        $this->loginComoAdmin();

        $result = $this->post('criancas', [
            'nome' => 'ab',
        ]);

        $result->assertSessionHas('erros');
        $this->assertSame(2, (new CriancaModel())->countAllResults());
    }

    public function testEditarCrianca(): void
    {
        $this->loginComoAdmin();

        $result = $this->post('criancas/atualizar/1', [
            'nome'   => 'Ana Teste Atualizada',
            'status' => 'ativo',
        ]);

        $result->assertRedirectTo('/criancas');
        $this->assertSame('Ana Teste Atualizada', (new CriancaModel())->find(1)['nome']);
    }

    public function testExcluirCrianca(): void
    {
        $this->loginComoAdmin();

        $result = $this->get('criancas/excluir/1');

        $result->assertRedirectTo('/criancas');
        $this->assertSame(1, (new CriancaModel())->countAllResults());
    }

    public function testAdicionarResponsavel(): void
    {
        $this->loginComoAdmin();

        $result = $this->post('criancas/1/responsaveis', [
            'nome'       => 'Maria Teste',
            'parentesco' => 'Mãe',
        ]);

        $result->assertRedirect();
        $this->assertCount(1, (new \App\Models\ResponsavelModel())->where('crianca_id', 1)->findAll());
    }

    public function testFuncionarioNaoAcessaUsuarios(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Funcionário',
            'usuario_email' => 'func@teste.com',
            'perfil'        => 'funcionario',
        ]);

        $result = $this->get('usuarios');

        $result->assertRedirectTo('/dashboard');
    }

    public function testPaginaUsuariosCarregaParaAdmin(): void
    {
        $this->loginComoAdmin();

        $result = $this->get('usuarios');

        $result->assertStatus(200);
        $result->assertSee('Usuários');
    }

    public function testCriarUsuario(): void
    {
        $this->loginComoAdmin();

        $result = $this->post('usuarios', [
            'nome'   => 'Novo Usuário',
            'email'  => 'novo@teste.com',
            'senha'  => '123456',
            'perfil' => 'funcionario',
        ]);

        $result->assertRedirectTo('/usuarios');
        $this->assertSame(2, (new \App\Models\UserModel())->countAllResults());
    }
}
