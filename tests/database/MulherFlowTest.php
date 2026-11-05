<?php

use App\Models\MulherModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do fluxo do perfil "mulher" (cadastro, login e dashboard).
 *
 * @internal
 */
final class MulherFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    private function dadosValidos(): array
    {
        return [
            'nome_completo'    => 'Maria Souza Silva',
            'nome_social'      => 'Marina',
            'cpf'              => '123.456.789-00',
            'rg'               => '12.345.678-9',
            'data_nascimento'  => '1990-05-15',
            'sexo'             => 'F',
            'estado_civil'     => 'casada',
            'escolaridade'     => 'medio_completo',
            'profissao'        => 'Professora',
            'ocupacao'         => 'Docente',
            'telefone'         => '(11) 99999-1111',
            'whatsapp'         => '(11) 99999-1112',
            'email'            => 'maria@teste.com',
            'cep'              => '01310-100',
            'logradouro'       => 'Av. Paulista',
            'numero'           => '100',
            'complemento'      => 'Apto 42',
            'bairro'           => 'Bela Vista',
            'municipio'        => 'São Paulo',
            'estado'           => 'SP',
            'ponto_referencia' => 'Próximo ao metrô',
            'zona'             => 'urbana',
            'possui_filhos'    => '1',
            'esta_gravida'     => '0',
            'possui_deficiencia' => '0',
            'e_idosa'          => '0',
            'situacao_vulnerabilidade' => '1',
            'observacoes'      => 'Acompanhamento psicológico.',
            'senha'            => 'senha123',
            'confirma_senha'   => 'senha123',
        ];
    }

    private function loginComoMulher(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Mulher Teste',
            'usuario_email' => 'mulher@teste.com',
            'perfil'        => 'mulher',
        ]);
    }

    public function testPaginaCadastroAbrePublicamente(): void
    {
        $result = $this->get('cadastro');

        $result->assertStatus(200);
        $result->assertSee('Cadastro da Mulher');
    }

    public function testCadastroCriaUsuarioComPerfilMulher(): void
    {
        $result = $this->post('cadastro/registrar', $this->dadosValidos());

        $result->assertRedirectTo('/login');

        $usuario = (new UserModel())->where('email', 'maria@teste.com')->first();
        $this->assertNotNull($usuario);
        $this->assertSame('mulher', $usuario['perfil']);
        $this->assertSame(1, (int) $usuario['ativo']);
        $this->assertNotSame('senha123', $usuario['senha']);
        $this->assertTrue(password_verify('senha123', $usuario['senha']));

        $dados = (new MulherModel())->findByUserId($usuario['id']);
        $this->assertNotNull($dados);
        $this->assertSame('Maria Souza Silva', $dados['nome_completo']);
        $this->assertSame('São Paulo', $dados['municipio']);
        $this->assertSame(1, (int) $dados['situacao_vulnerabilidade']);
    }

    public function testCadastroRejeitaEmailDuplicado(): void
    {
        $dados = $this->dadosValidos();
        $dados['email'] = 'admin@teste.com';

        $result = $this->post('cadastro/registrar', $dados);

        $result->assertSessionHas('erros');
        $this->assertNull((new UserModel())->where('email', 'maria@teste.com')->first());
    }

    public function testCadastroRejeitaSenhaDiferente(): void
    {
        $dados = $this->dadosValidos();
        $dados['confirma_senha'] = 'outra-senha';

        $result = $this->post('cadastro/registrar', $dados);

        $result->assertSessionHas('erros');
        $this->assertNull((new UserModel())->where('email', 'maria@teste.com')->first());
    }

    public function testCadastroRejeitaFormularioSemPerfilForcado(): void
    {
        // Envia perfil 'admin' no POST; o servidor deve ignorar e gravar 'mulher'.
        $dados = $this->dadosValidos();
        $dados['perfil'] = 'admin';

        $result = $this->post('cadastro/registrar', $dados);

        $result->assertRedirectTo('/login');
        $usuario = (new UserModel())->where('email', 'maria@teste.com')->first();
        $this->assertNotNull($usuario);
        $this->assertSame('mulher', $usuario['perfil']);
    }

    public function testLoginMulherRedirecionaParaDashboardMulher(): void
    {
        $this->post('cadastro/registrar', $this->dadosValidos());

        $result = $this->post('login/autenticar', [
            'email' => 'maria@teste.com',
            'senha' => 'senha123',
        ]);

        $result->assertRedirectTo('/mulher/dashboard');
        $result->assertSessionHas('logado');
        $result->assertSessionHas('perfil', 'mulher');
    }

    public function testDashboardMulherCarrega(): void
    {
        $this->loginComoMulher();

        $result = $this->get('mulher/dashboard');

        $result->assertStatus(200);
        $result->assertSee('Pedido SOS');
    }

    public function testDashboardMulherExigeLogin(): void
    {
        $result = $this->get('mulher/dashboard');

        $result->assertRedirectTo('/login');
    }

    public function testMulherNaoAcessaPaginasDeAdmin(): void
    {
        $this->loginComoMulher();

        $result = $this->get('usuarios');

        $result->assertRedirectTo('/dashboard');
    }

    public function testAdminNaoAcessaDashboardMulher(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Admin Teste',
            'usuario_email' => 'admin@teste.com',
            'perfil'        => 'admin',
        ]);

        $result = $this->get('mulher/dashboard');

        $result->assertRedirectTo('/dashboard');
    }

    public function testFuncionarioContinuaAcessandoDashboard(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Funcionário',
            'usuario_email' => 'func@teste.com',
            'perfil'        => 'funcionario',
        ]);

        $result = $this->get('dashboard');

        $result->assertStatus(200);
    }

    public function testPaginaPerfilMulherCarrega(): void
    {
        $this->loginComoMulher();

        $result = $this->get('mulher/perfil');

        $result->assertStatus(200);
        $result->assertSee('Meus Dados');
    }
}