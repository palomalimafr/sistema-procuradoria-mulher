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
 * Coerência da identidade da mulher entre os quatro pontos de entrada:
 * cadastro da equipe, primeiro acesso (convite), cadastro público e edição.
 *
 * A regra central: `dados_mulher` guarda UMA ficha por mulher. O e-mail
 * identifica a ficha existente e o CPF confirma a identidade; o token do
 * convite é o ÚNICO meio de criar a conta de acesso sobre uma ficha que a
 * equipe cadastrou. O cadastro público nunca pode abrir uma segunda ficha
 * para o mesmo e-mail nem para o mesmo CPF.
 *
 * @internal
 */
final class MulherIdentidadeUnicaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use EmailMockTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockarEmail();
    }

    // --- Caso A: equipe cadastra, convite conclui o primeiro acesso ---------

    public function testEquipeCadastraEPrimeiroAcessoVinculaAFicha(): void
    {
        $this->logarComo('admin');

        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Maria da Silva',
            'cpf'           => '111.222.333-44',
            'email'         => 'maria.primeiro.acesso@exemplo.test',
        ]))->assertRedirect();

        // Ficha criada, sem conta, com convite enviado.
        $mulher = $this->mulherPorEmail('maria.primeiro.acesso@exemplo.test');
        $this->assertNotNull($mulher, 'A equipe deve criar a ficha da mulher.');
        $this->assertEmpty($mulher['user_id'], 'A ficha da equipe começa sem conta de acesso.');

        $convite = (new ConviteMulherModel())->pendente((int) $mulher['id']);
        $this->assertNotNull($convite, 'O cadastro da equipe deve gerar um convite em aberto.');

        // O e-mail saiu com o link de primeiro acesso (nunca o /cadastro).
        $this->assertStringContainsString('primeiro-acesso/', $this->corpoDoUltimoEmail());

        // Primeiro acesso: a mulher define a senha e a ficha é vinculada.
        [$id, $token] = $this->conviteValidoDe('maria.primeiro.acesso@exemplo.test');

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ])->assertRedirectTo('/login');

        $vinculada = (new MulherModel())->find($id);
        $this->assertNotSame('', (string) $vinculada['user_id'], 'A ficha deve ficar vinculada à conta criada.');

        $conta = (new UserModel())->find((int) $vinculada['user_id']);
        $this->assertSame('maria.primeiro.acesso@exemplo.test', $conta['email']);
        $this->assertTrue(password_verify('segredo123', $conta['senha']), 'A senha é a que a mulher definiu.');
    }

    public function testLoginFuncionaDepoisDoPrimeiroAcesso(): void
    {
        $this->logarComo('admin');
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Mariana Login',
            'cpf'           => '222.333.444-55',
            'email'         => 'mariana.login@exemplo.test',
        ]));

        [, $token] = $this->conviteValidoDe('mariana.login@exemplo.test');
        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'senha-da-mulher',
            'confirma_senha' => 'senha-da-mulher',
        ]);

        $this->withSession([]);

        $this->post('login/autenticar', [
            'email' => 'mariana.login@exemplo.test',
            'senha' => 'senha-da-mulher',
        ])->assertRedirectTo('/mulher/dashboard');

        $this->assertSame('mulher', session('perfil'));
    }

    // --- Caso B/C/D: cadastro público não pode abrir segunda ficha -----------

    public function testCadastroPublicoNaoCriaSegundaFichaQuandoEquipeJaCadastrou(): void
    {
        $this->logarComo('admin');
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Joana Reaproveitada',
            'cpf'           => '333.444.555-66',
            'email'         => 'joana.reaproveitada@exemplo.test',
        ]));

        $fichasAntes = $this->totalDeFichas();
        $this->withSession([]);

        $result = $this->post('cadastro/registrar', $this->dadosPublicos([
            'nome_completo' => 'Joana Reaproveitada',
            'email'         => 'joana.reaproveitada@exemplo.test',
        ]));

        $result->assertSessionHas('erros', ['email' => 'Já existe um cadastro seu com este e-mail. Use o convite de primeiro acesso enviado pela equipe para criar sua senha.']);
        $this->assertSame($fichasAntes, $this->totalDeFichas(), 'O cadastro público não pode duplicar a ficha.');
    }

    public function testCadastroPublicoNaoCriaFichaQuandoFichaAindaAguardaConvite(): void
    {
        $this->logarComo('admin');
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Aguardando Convite',
            'cpf'           => '444.555.666-77',
            'email'         => 'aguardando.convite@exemplo.test',
        ]));

        $ficha = $this->mulherPorEmail('aguardando.convite@exemplo.test');
        $this->assertEmpty((string) $ficha['user_id'], 'Pré-condição: a ficha está sem conta, aguardando o convite.');

        $fichasAntes = $this->totalDeFichas();
        $this->withSession([]);

        $this->post('cadastro/registrar', $this->dadosPublicos([
            'nome_completo' => 'Aguardando Convite',
            'email'         => 'aguardando.convite@exemplo.test',
        ]))->assertSessionHas('erros');

        $this->assertSame($fichasAntes, $this->totalDeFichas());
    }

    public function testCadastroPublicoNaoCriaFichaQuandoJaExisteConta(): void
    {
        $this->logarComo('admin');
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Conta Pronta',
            'cpf'           => '555.666.777-88',
            'email'         => 'conta.pronta@exemplo.test',
        ]));

        [, $token] = $this->conviteValidoDe('conta.pronta@exemplo.test');
        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $fichasAntes = $this->totalDeFichas();
        $this->withSession([]);

        $result = $this->post('cadastro/registrar', $this->dadosPublicos([
            'nome_completo' => 'Conta Pronta',
            'email'         => 'conta.pronta@exemplo.test',
        ]));

        $result->assertSessionHas('erros', ['email' => 'Já existe uma conta de acesso com este e-mail. Faça login ou use a recuperação de acesso.']);
        $this->assertSame($fichasAntes, $this->totalDeFichas());
    }

    public function testCadastroPublicoRecusaEmailDeUsuarioDaEquipe(): void
    {
        $fichasAntes = $this->totalDeFichas();
        $this->withSession([]);

        // admin@teste.com é a conta da equipe criada pelo TestDataSeeder.
        $this->post('cadastro/registrar', $this->dadosPublicos([
            'email' => 'admin@teste.com',
        ]))->assertSessionHas('erros', ['email' => 'Este e-mail já está em uso por um usuário do sistema.']);

        $this->assertSame($fichasAntes, $this->totalDeFichas());
    }

    public function testCadastroPublicoRecusaCpfJaCadastradoPelaEquipe(): void
    {
        $this->logarComo('admin');
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Cpf Repetido',
            'cpf'           => '666.777.888-99',
            'email'         => 'cpf.repetido@exemplo.test',
        ]));

        $fichasAntes = $this->totalDeFichas();
        $this->withSession([]);

        $this->post('cadastro/registrar', $this->dadosPublicos([
            'nome_completo' => 'Cpf Repetido Outro',
            'email'         => 'outro.email.livre@exemplo.test',
            // Mesmo CPF, com máscara diferente: a regra ignora pontuação.
            'cpf'           => '66677788899',
        ]))->assertSessionHas('erros', ['cpf' => 'Já existe uma ficha com este CPF.']);

        $this->assertSame($fichasAntes, $this->totalDeFichas());
    }

    public function testCadastroPublicoIgnoraMaiusculasNoEmail(): void
    {
        $this->logarComo('admin');
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Teste Maiusculas',
            'cpf'           => '777.888.999-00',
            'email'         => 'minusculas@exemplo.test',
        ]));

        $fichasAntes = $this->totalDeFichas();
        $this->withSession([]);

        $this->post('cadastro/registrar', $this->dadosPublicos([
            'email' => 'MINUSCULAS@EXEMPLO.TEST',
        ]))->assertSessionHas('erros');

        $this->assertSame($fichasAntes, $this->totalDeFichas());
    }

    public function testCadastroPublicoNaoConsomeConviteExistente(): void
    {
        $this->logarComo('admin');
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Convite Intacto',
            'cpf'           => '888.999.000-11',
            'email'         => 'convite.intacto@exemplo.test',
        ]));

        [$id, $token] = $this->conviteValidoDe('convite.intacto@exemplo.test');
        $this->withSession([]);

        // O cadastro público é recusado e NÃO pode consumir o convite: quem
        // autoriza o primeiro acesso é o token, não o formulário público.
        $this->post('cadastro/registrar', $this->dadosPublicos([
            'email' => 'convite.intacto@exemplo.test',
        ]))->assertSessionHas('erros');

        $convite = (new ConviteMulherModel())->ultimo($id);
        $this->assertSame(
            ConviteMulherModel::STATUS_VALIDO,
            (new ConviteMulherModel())->statusDe($convite),
            'O convite precisa continuar válido para a mulher usar o link.'
        );

        // E o token segue autorizando o primeiro acesso normalmente.
        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ])->assertRedirectTo('/login');

        $this->assertNotEmpty((new MulherModel())->find($id)['user_id']);
    }

    // --- edição não pode abrir ficha paralela --------------------------------

    public function testEdicaoComEmailDeOutraMulherEhRecusada(): void
    {
        $this->logarComo('admin');

        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Primeira Ficha',
            'cpf'           => '111.000.111-00',
            'email'         => 'primeira.ficha@exemplo.test',
        ]));
        $this->post('mulheres/cadastro/salvar', $this->dadosDaEquipe([
            'nome_completo' => 'Segunda Ficha',
            'cpf'           => '222.000.222-00',
            'email'         => 'segunda.ficha@exemplo.test',
        ]));

        $segunda = $this->mulherPorEmail('segunda.ficha@exemplo.test');

        $this->post('mulheres/atualizar/' . $segunda['id'], $this->dadosDaEquipe([
            'nome_completo' => 'Segunda Ficha',
            'cpf'           => '222.000.222-00',
            'email'         => 'primeira.ficha@exemplo.test',
        ]))->assertSessionHas('erros');
        $this->assertArrayHasKey('email', session('erros'), 'A edição deve recusar o e-mail de outra ficha.');

        $this->assertNotNull(
            $this->mulherPorEmail('segunda.ficha@exemplo.test'),
            'A edição recusada não pode trocar o e-mail da ficha.'
        );
    }

    // --- apoio ---------------------------------------------------------------

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

    private function dadosDaEquipe(array $alteracoes = []): array
    {
        return array_merge([
            'nome_completo'    => 'Ana Clara Ferreira',
            'nome_social'      => '',
            'cpf'              => '123.456.789-01',
            'rg'               => '',
            'data_nascimento'  => '1985-07-20',
            'sexo'             => 'F',
            'estado_civil'     => 'solteira',
            'escolaridade'     => 'medio',
            'profissao'        => 'Costureira',
            'ocupacao'         => 'Autônoma',
            'telefone'         => '(22) 2722-0000',
            'whatsapp'         => '(22) 99999-0000',
            'email'            => 'ana.clara@exemplo.test',
            'cep'              => '28013-000',
            'logradouro'       => 'Rua das Flores',
            'numero'           => '120',
            'complemento'      => '',
            'bairro'           => 'Centro',
            'municipio'        => 'Campos dos Goytacazes',
            'estado'           => 'RJ',
            'ponto_referencia' => '',
            'zona'             => 'urbana',
            'possui_filhos'    => '0',
            'quantidade_filhos' => '',
            'quantidade_dependentes' => '',
            'esta_gravida'     => '0',
            'possui_deficiencia' => '0',
            'e_idosa'          => '0',
            'situacao_vulnerabilidade' => '0',
            'observacoes'      => '',
        ], $alteracoes);
    }

    private function dadosPublicos(array $alteracoes = []): array
    {
        return array_merge([
            'nome_completo'   => 'Carla Publica',
            'nome_social'     => '',
            'email'           => 'carla.publica@exemplo.test',
            'senha'           => 'senha123',
            'confirma_senha'  => 'senha123',
            'cpf'             => '999.888.777-66',
            'rg'              => '',
            'data_nascimento' => '1990-05-15',
            'sexo'            => 'F',
            'estado_civil'    => 'solteira',
            'escolaridade'    => 'medio',
            'profissao'       => 'Costureira',
            'ocupacao'        => 'Autônoma',
            'telefone'        => '(22) 2722-0001',
            'whatsapp'        => '(22) 99999-0001',
            'cep'             => '28013-000',
            'logradouro'      => 'Rua Aberta',
            'numero'          => '10',
            'complemento'     => '',
            'bairro'          => 'Centro',
            'municipio'       => 'Campos dos Goytacazes',
            'estado'          => 'RJ',
            'ponto_referencia' => '',
            'zona'            => 'urbana',
            'possui_filhos'   => '0',
            'esta_gravida'    => '0',
            'possui_deficiencia' => '0',
            'e_idosa'         => '0',
            'situacao_vulnerabilidade' => '0',
            'observacoes'     => '',
        ], $alteracoes);
    }

    /**
     * @return array{0: int, 1: string} id da ficha e token em texto puro
     */
    private function conviteValidoDe(string $email): array
    {
        $mulher = $this->mulherPorEmail($email);
        $this->assertNotNull($mulher, "A ficha de {$email} precisa existir para o teste.");

        $convite = (new ConviteMulherModel())->gerar((int) $mulher['id'], 24);

        return [(int) $mulher['id'], $convite['token']];
    }

    private function mulherPorEmail(string $email): ?array
    {
        return (new MulherModel())
            ->groupStart()
            ->where('email IS NOT NULL')
            ->where('LOWER(email)', mb_strtolower($email))
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();
    }

    private function totalDeFichas(): int
    {
        return (new MulherModel())->countAllResults();
    }

    /** Corpo da última mensagem enviada, para conferir o link do convite. */
    private function corpoDoUltimoEmail(): string
    {
        $email = $this->ultimoEmailEnviado();

        $this->assertNotNull($email, 'Nenhum e-mail foi enviado.');

        return (string) ($email['body'] ?? '');
    }
}
