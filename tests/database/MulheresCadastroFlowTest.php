<?php

use App\Models\ConviteMulherModel;
use App\Models\MulherModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;
use Tests\Support\EmailMockTrait;

/**
 * Testes do cadastro ADMINISTRATIVO de mulheres (MULHERES > Cadastro).
 *
 * @internal
 */
final class MulheresCadastroFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use EmailMockTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // O cadastro agora dispara convite por e-mail; sem mock o teste
        // dependeria de um SMTP real.
        $this->mockarEmail();
    }

    private function logarComo(string $perfil): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Equipe Teste',
            'usuario_email' => 'equipe@teste.com',
            'perfil'        => $perfil,
        ]);
    }

    private function dadosAdministrativos(array $alteracoes = []): array
    {
        return array_merge([
            'nome_completo'          => 'Ana Clara Ferreira',
            'nome_social'            => 'Aninha',
            'cpf'                    => '123.456.789-01',
            'rg'                     => '22.333.444-5',
            'data_nascimento'        => '1985-07-20',
            'sexo'                   => 'F',
            'estado_civil'           => 'divorciada',
            'escolaridade'           => 'medio',
            'profissao'              => 'Costureira',
            'ocupacao'               => 'Autônoma',
            'telefone'               => '(22) 2722-0000',
            'whatsapp'               => '(22) 99999-0000',
            'email'                  => 'ana.clara@exemplo.test',
            'cep'                    => '28013-000',
            'logradouro'             => 'Rua das Flores',
            'numero'                 => '120',
            'complemento'            => 'Casa',
            'bairro'                 => 'Centro',
            'municipio'              => 'Campos dos Goytacazes',
            'estado'                 => 'RJ',
            'ponto_referencia'       => 'Próximo à praça',
            'zona'                   => 'urbana',
            'possui_filhos'          => '1',
            'quantidade_filhos'      => '2',
            'quantidade_dependentes' => '3',
            'esta_gravida'           => '0',
            'possui_deficiencia'     => '0',
            'e_idosa'                => '0',
            'situacao_vulnerabilidade' => '1',
            'observacoes'            => 'Encaminhada pela assistência social.',
        ], $alteracoes);
    }

    public function testPaginaCadastroAdministrativoCarregaParaEquipe(): void
    {
        $this->logarComo('admin');

        $result = $this->get('mulheres/cadastro');

        $result->assertStatus(200);
        $result->assertSee('Dados pessoais');
        $result->assertSee('Informações complementares');
    }

    public function testAdminCadastraMulherSemContaDeAcesso(): void
    {
        $this->logarComo('admin');

        $result = $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos());

        $result->assertRedirectTo('/mulheres/cadastro');

        $mulher = (new MulherModel())->where('nome_completo', 'Ana Clara Ferreira')->first();
        $this->assertNotNull($mulher);
        $this->assertNull($mulher['user_id'], 'Cadastro administrativo não deve criar vínculo com conta.');
        $this->assertSame('12345678901', $mulher['cpf'], 'CPF deve ser persistido sem máscara.');
        $this->assertSame('divorciada', $mulher['estado_civil']);
        $this->assertSame('Costureira', $mulher['profissao']);
        $this->assertSame('Rua das Flores', $mulher['logradouro']);
        $this->assertSame('RJ', $mulher['estado']);
        $this->assertSame('urbana', $mulher['zona']);
        $this->assertSame(1, (int) $mulher['possui_filhos']);
        $this->assertSame(2, (int) $mulher['quantidade_filhos']);
        $this->assertSame(3, (int) $mulher['quantidade_dependentes']);
        $this->assertSame(1, (int) $mulher['situacao_vulnerabilidade']);
    }

    public function testFichaMostraConviteEnviadoAposCadastro(): void
    {
        $this->logarComo('admin');

        $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos());

        $mulher = (new MulherModel())->where('cpf', '12345678901')->first();

        $result = $this->get('mulheres/detalhes/' . $mulher['id']);

        $result->assertStatus(200);
        $texto = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Convite enviado', $texto);
        $this->assertStringContainsString('Reenviar', $texto);
    }

    public function testReenvioDeConviteGerNovoTokenInvalidoOAnterior(): void
    {
        $this->logarComo('admin');

        $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos());
        $mulher = (new MulherModel())->where('cpf', '12345678901')->first();

        $modelo = new ConviteMulherModel();
        $antes  = $modelo->ultimo((int) $mulher['id']);
        $this->assertNotNull($antes);
        $this->assertSame(ConviteMulherModel::STATUS_VALIDO, $modelo->statusDe($antes));

        $resultado = $this->post('mulheres/convite/' . $mulher['id']);

        $resultado->assertRedirectTo('/mulheres/detalhes/' . $mulher['id']);

        $depois = $modelo->ultimo((int) $mulher['id']);
        $this->assertNotSame($antes['token_hash'], $depois['token_hash'], 'Reenviar deve gerar um token novo.');
        $this->assertSame(ConviteMulherModel::STATUS_VALIDO, $modelo->statusDe($depois));

        // O convite anterior é cancelado, não consumido: nenhum dos dois criou
        // conta, então o link velho não pode dizer que a conta já existe.
        $antigo = $modelo->where('token_hash', $antes['token_hash'])->first();
        $this->assertSame(ConviteMulherModel::STATUS_INVALIDADO, $modelo->statusDe($antigo));
        $this->assertNull($modelo->pendentePorHash($antes['token_hash']));
    }

    public function testNaoCriaUsuarioDeLogin(): void
    {
        $this->logarComo('admin');

        $dados = $this->dadosAdministrativos(['email' => 'ana@semconta.com']);
        $this->post('mulheres/cadastro/salvar', $dados);

        $this->assertNull(
            (new UserModel())->where('email', 'ana@semconta.com')->first(),
            'O cadastro administrativo nunca deve criar conta de acesso.'
        );
    }

    public function testCpfRepetidoReaproveitaMulherSemDuplicar(): void
    {
        $this->logarComo('admin');

        $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos());

        $resultado = $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos([
            'nome_completo' => 'Ana Clara Ferreira Lima',
            'telefone'      => '(22) 99999-1111',
        ]));

        $resultado->assertRedirectTo('/mulheres/cadastro');

        $model = new MulherModel();
        $this->assertSame(1, $model->where('cpf', '12345678901')->countAllResults(), 'CPF repetido não pode duplicar o cadastro.');

        $mulher = $model->where('cpf', '12345678901')->first();
        $this->assertSame('Ana Clara Ferreira Lima', $mulher['nome_completo']);
    }

    public function testVinculaPorEmailQuandoMulherJaTemConta(): void
    {
        $userId = (new UserModel())->insert([
            'nome'   => 'Juliana Conta',
            'email'  => 'juliana@conta.com',
            'senha'  => 'senha123',
            'perfil' => 'mulher',
            'ativo'  => 1,
        ]);
        $this->assertIsInt($userId);

        $this->logarComo('admin');

        $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos([
            'nome_completo' => 'Juliana Conta',
            'cpf'           => '987.654.321-00',
            'email'         => 'juliana@conta.com',
        ]));

        $mulher = (new MulherModel())->where('cpf', '98765432100')->first();
        $this->assertNotNull($mulher);
        $this->assertSame($userId, (int) $mulher['user_id'], 'Deve vincular à conta existente sem criar nova.');
        $this->assertSame(1, (new UserModel())->where('email', 'juliana@conta.com')->countAllResults());
    }

    public function testCadastroPublicoTemCorrespondenciaReaproveitada(): void
    {
        $this->post('cadastro/registrar', [
            'nome_completo'   => 'Maria Souza Silva',
            'email'           => 'maria@teste.com',
            'senha'           => 'senha123',
            'confirma_senha'  => 'senha123',
            'cpf'             => '529.982.247-25',
            'data_nascimento' => '1990-05-15',
        ]);

        $conta = (new UserModel())->where('email', 'maria@teste.com')->first();
        $this->assertNotNull($conta);

        $this->logarComo('admin');

        $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos([
            'nome_completo' => 'Maria Souza Silva',
            'cpf'           => '52998224725',
            'email'         => 'maria@teste.com',
        ]));

        $model = new MulherModel();
        $this->assertSame(1, $model->where('cpf', '52998224725')->countAllResults(), 'Não deve duplicar mulher que já se cadastrou pelo dispositivo.');

        $mulher = $model->where('cpf', '52998224725')->first();
        $this->assertSame((int) $conta['id'], (int) $mulher['user_id']);
    }

    public function testFuncionarioTambemPodeCadastrar(): void
    {
        $this->logarComo('funcionario');

        $result = $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos([
            'nome_completo' => 'Bia Funcionaria',
            'email'         => 'bia.funcionaria@exemplo.test',
        ]));

        $result->assertRedirectTo('/mulheres/cadastro');
        $this->assertNotNull((new MulherModel())->where('nome_completo', 'Bia Funcionaria')->first());
    }

    public function testMulherNaoAcessaCadastroAdministrativo(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('mulheres/cadastro');
        $result->assertRedirectTo('/dashboard');
    }

    public function testVisitanteNaoAcessaCadastroAdministrativo(): void
    {
        $result = $this->get('mulheres/cadastro');
        $result->assertRedirectTo('/login');
    }

    public function testValidaNomeObrigatorio(): void
    {
        $this->logarComo('admin');

        $result = $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos(['nome_completo' => '']));

        $result->assertSessionHas('erros');
        $this->assertSame(0, (new MulherModel())->countAllResults());
    }

    public function testRejeitaCpfInvalido(): void
    {
        $this->logarComo('admin');

        $result = $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos(['cpf' => '123']));

        $result->assertSessionHas('erros');
        $this->assertSame(0, (new MulherModel())->countAllResults());
    }

    public function testRejeitaDataNascimentoFutura(): void
    {
        $this->logarComo('admin');

        $result = $this->post('mulheres/cadastro/salvar', $this->dadosAdministrativos(['data_nascimento' => date('Y-m-d', strtotime('+1 year'))]));

        $result->assertSessionHas('erros');
        $this->assertSame(0, (new MulherModel())->countAllResults());
    }
}