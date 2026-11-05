<?php

use App\Models\ConviteMulherModel;
use App\Models\ConfiguracaoModel;
use App\Models\MulherModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockEmail;
use Config\Services;
use Tests\Support\Database\Seeds\TestDataSeeder;
use Tests\Support\Libraries\TransporteRecusadoEmail;

/**
 * Testes do PRIMEIRO ACESSO da mulher cadastrada pela equipe.
 *
 * O caminho exercitado é o real: rota pública -> PrimeiroAcesso -> users +
 * dados_mulher.user_id + consumo do convite. O e-mail é capturado pelo
 * MockEmail do framework, sem tocar na rede.
 *
 * @internal
 */
final class PrimeiroAcessoFluxoTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    // --- telas e recusas -----------------------------------------------------

    public function testTelaExibeFormularioComConviteValido(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $result = $this->get('primeiro-acesso/' . $token);

        $result->assertStatus(200);
        $result->assertSee('Criar minha senha');
        $result->assertSee('Criar minha conta');
    }

    public function testTelaNaoRevelaEmailCompletoDaMulher(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $result = $this->get('primeiro-acesso/' . $token);

        $email = $this->emailDaUltimaMulher();
        $result->assertDontSee($email);
        $result->assertSee('***@');
    }

    // --- sessão administrativa ativa não pode desviar o convite ---------------
    //
    // O link é testado no navegador da equipe, que está autenticado. A rota é
    // pública e a autorização é o token: estar logado não pode trocar a tela
    // de criação de senha por um dashboard.

    public function testTelaAbreComSessaoAdminAtiva(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->withSession($this->sessaoAdmin());

        $result = $this->get('primeiro-acesso/' . $token);

        $result->assertStatus(200);
        $result->assertSee('Criar minha senha');
        $result->assertDontSee('Dashboard');
    }

    public function testTelaNaoRedirecionaParaDashboardAdmin(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->withSession($this->sessaoAdmin());

        $result = $this->get('primeiro-acesso/' . $token);

        $this->assertFalse(
            $result->isRedirect(),
            'Com sessão de admin ativa o convite não pode virar redirecionamento.'
        );
    }

    public function testTelaNaoRedirecionaParaDashboardDaMulherLogada(): void
    {
        [, $token] = $this->mulherComConviteValido();

        // Sessão de outra mulher, sem relação com o convite.
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 77,
            'usuario_nome'  => 'Outra Mulher',
            'usuario_email' => 'outra.mulher@exemplo.test',
            'perfil'        => 'mulher',
        ]);

        $result = $this->get('primeiro-acesso/' . $token);

        $result->assertStatus(200);
        $result->assertSee('Criar minha senha');
    }

    public function testTokenContinuaNaUrlDoFormAposAbrirComSessaoAdmin(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->withSession($this->sessaoAdmin());

        $result = $this->get('primeiro-acesso/' . $token);

        // O token tem de chegar intacto no action do formulário, senão o
        // POST seguinte não tem como autorizar o primeiro acesso.
        $this->assertStringContainsString(
            'primeiro-acesso/' . $token,
            (string) $result->getBody(),
            'O action do formulário precisa preservar o token do convite.'
        );
    }

    public function testPrimeiroAcessoConcluiComSessaoAdminAtiva(): void
    {
        [$mulherId, $token] = $this->mulherComConviteValido();

        $this->withSession($this->sessaoAdmin());

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ])->assertRedirectTo('/login');

        // A conta da mulher nasce e a ficha fica vinculada normalmente.
        $mulher = (new MulherModel())->find($mulherId);
        $this->assertNotEmpty((string) $mulher['user_id'], 'A ficha deve ser vinculada mesmo com admin logado.');

        $user = (new UserModel())->find((int) $mulher['user_id']);
        $this->assertSame('mulher', $user['perfil']);
        $this->assertTrue(password_verify('segredo123', $user['senha']));

        $convite = (new ConviteMulherModel())->ultimo($mulherId);
        $this->assertSame(ConviteMulherModel::STATUS_UTILIZADO, (new ConviteMulherModel())->statusDe($convite));
    }

    public function testSessaoAdminNaoEhDesfeitaPeloPrimeiroAcesso(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->withSession($this->sessaoAdmin());

        $this->get('primeiro-acesso/' . $token);
        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        // Abrir o convite de outra mulher não pode derrubar a sessão de quem
        // estava usando o sistema.
        $this->assertTrue(session('logado'));
        $this->assertSame('admin', session('perfil'));
    }

    public function testConviteInvalidoContinuaRecusadoComSessaoAdminAtiva(): void
    {
        [, $token] = $this->mulherComConviteValido();
        $this->expirarConvite();

        $this->withSession($this->sessaoAdmin());

        $result = $this->get('primeiro-acesso/' . $token);

        $result->assertStatus(410);
        $result->assertSee('expirou');
    }

    public function testTokenInexistenteRecusado(): void
    {
        $result = $this->get('primeiro-acesso/token-que-nunca-existiu');

        $result->assertStatus(404);
        $result->assertSee('Link de primeiro acesso inválido');
    }

    public function testTokenExpiradoRecusado(): void
    {
        [, $token] = $this->mulherComConviteValido();
        $this->expirarConvite();

        $result = $this->get('primeiro-acesso/' . $token);

        $result->assertStatus(410);
        $result->assertSee('expirou');
    }

    public function testConviteUtilizadoRecusado(): void
    {
        [, $token] = $this->mulherComConviteValido();
        $this->utilizarConvite();

        $result = $this->get('primeiro-acesso/' . $token);

        $result->assertStatus(410);
        $result->assertSee('já foi utilizado');
    }

    public function testConviteUtilizadoRecusadoNoPost(): void
    {
        [, $token] = $this->mulherComConviteValido();
        $this->utilizarConvite();

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $result->assertStatus(410);
        $this->assertNull($this->userDaUltimaMulher());
    }

    // --- validação de senha --------------------------------------------------

    public function testSenhaCurtaRecusada(): void
    {
        [$mulherId, $token] = $this->mulherComConviteValido();

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => '123',
            'confirma_senha' => '123',
        ]);

        $result->assertSessionHas('erros');
        $this->assertNull($this->userDaUltimaMulher(), 'Nenhuma conta pode ser criada com senha inválida.');
    }

    public function testSenhasDivergentesRecusadas(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'outra-senha',
        ]);

        $result->assertSessionHas('erros', ['confirma_senha' => 'As senhas não conferem.']);
        $this->assertNull($this->userDaUltimaMulher());
    }

    public function testSenhaVaziaRecusada(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => '',
            'confirma_senha' => '',
        ]);

        $result->assertSessionHas('erros');
        $this->assertNull($this->userDaUltimaMulher());
    }

    // --- criação da conta ----------------------------------------------------

    public function testCriaContaVinculadaAFicha(): void
    {
        [$mulherId, $token] = $this->mulherComConviteValido();

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('sucesso');

        $mulher = (new MulherModel())->find($mulherId);
        $this->assertNotNull($mulher['user_id'], 'A ficha deve ser vinculada à conta criada.');

        $user = (new UserModel())->find((int) $mulher['user_id']);
        $this->assertSame('mulher', $user['perfil']);
        $this->assertSame(1, (int) $user['ativo']);
    }

    public function testSenhaGravadaComHashESenhaEmPlanoNaoFica(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $user = $this->userDaUltimaMulher();
        $this->assertNotNull($user);
        $this->assertNotSame('segredo123', $user['senha'], 'A senha jamais pode ser gravada em claro.');
        $this->assertTrue(password_verify('segredo123', $user['senha']));
    }

    public function testNomeDaContaVemDaFicha(): void
    {
        [, $token] = $this->mulherComConviteValido('Beatriz Oliveira Santos');

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $this->assertSame('Beatriz Oliveira Santos', $this->userDaUltimaMulher()['nome']);
    }

    public function testConviteFicaConsumido(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $convite = (new ConviteMulherModel())->ultimo($this->ultimaMulherId());
        $this->assertSame(ConviteMulherModel::STATUS_UTILIZADO, (new ConviteMulherModel())->statusDe($convite));
    }

    public function testSegundoUsoDoTokenNaoTrocaSenha(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'primeira-senha',
            'confirma_senha' => 'primeira-senha',
        ]);

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segunda-senha',
            'confirma_senha' => 'segunda-senha',
        ]);

        $user = $this->userDaUltimaMulher();
        $this->assertTrue(password_verify('primeira-senha', $user['senha']));
        $this->assertFalse(password_verify('segunda-senha', $user['senha']), 'Convite de uso único não pode remarcar a senha.');
    }

    public function testNaoCriaSegundaContaParaMesmaFicha(): void
    {
        [, $token] = $this->mulherComConviteValido();

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'primeira-senha',
            'confirma_senha' => 'primeira-senha',
        ]);

        $antes = (new UserModel())->countAllResults();

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'terceira-senha',
            'confirma_senha' => 'terceira-senha',
        ]);

        $this->assertSame($antes, (new UserModel())->countAllResults());
    }

    // --- e-mail em conflito --------------------------------------------------

    public function testEmailInvalidoNaFichaImpedeCriacaoDeConta(): void
    {
        [$mulherId, $token] = $this->mulherComConviteValido();
        $this->corromperEmailDaUltimaMulher('endereco-sem-arroba');

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $result->assertSessionHas('erros');
        $this->assertNull($this->userDaUltimaMulher());
    }

    public function testEmailDeOutraMulherImpedeCriacaoDeConta(): void
    {
        [$mulherId, $token] = $this->mulherComConviteValido();
        $this->mulherSemConvite('Outra Mulher Com Mesmo Email', $this->emailDaUltimaMulher());

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $result->assertSessionHas('erros');
        $this->assertNull($this->userDaUltimaMulher());
    }

    /**
     * Duplicidade herdada: se uma ficha duplicada já existe no banco, o
     * primeiro acesso é barrado (criaria duas contas com o mesmo e-mail).
     * A tela precisa, nesse caso, dizer o que fazer — e não deixar a mulher
     * num beco sem saída que a empurre para o cadastro público.
     */
    public function testDuplicidadeHerdadaExplicaOCaminho(): void
    {
        [$mulherId, $token] = $this->mulherComConviteValido();
        $this->mulherSemConvite('Ficha Duplicada', $this->emailDaUltimaMulher());

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $erros = (array) session('erros');
        $texto = (string) reset($erros);

        $this->assertStringContainsString('verificar a duplicidade', $texto);
        $this->assertStringContainsString('reenviar o convite', $texto);
        $this->assertNull($this->userDaUltimaMulher());
    }

    public function testDuplicidadeHerdadaNaoConsomeOConvite(): void
    {
        // A duplicata é criada depois, então a ficha do convite não é a última.
        [$mulherId, $token] = $this->mulherComConviteValido();
        $this->mulherSemConvite('Ficha Duplicada', $this->emailDaUltimaMulher());

        $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $convite = (new ConviteMulherModel())->ultimo($mulherId);
        $this->assertSame(
            ConviteMulherModel::STATUS_VALIDO,
            (new ConviteMulherModel())->statusDe($convite),
            'Convite barrado por duplicidade continua válido para depois da correção.'
        );
    }

    public function testEmailDeUsuarioDaEquipeImpedeCriacaoDeConta(): void
    {
        [, $token] = $this->mulherComConviteValido();

        (new UserModel())->insert([
            'nome'   => 'Servidor de Teste',
            'email'  => $this->emailDaUltimaMulher(),
            'senha'  => 'senha-da-equipe',
            'perfil' => 'funcionario',
            'ativo'  => 1,
        ]);

        $result = $this->post('primeiro-acesso/' . $token, [
            'senha'          => 'segredo123',
            'confirma_senha' => 'segredo123',
        ]);

        $result->assertSessionHas('erros');
        $this->assertNull($this->userDaUltimaMulher());
    }

    // --- convite inexistente não vira massa -----------------------------------

    public function testConviteAdivinhadoNaoCriaConta(): void
    {
        $mulherId = $this->mulherSemConvite('Maria Sem Convite', 'sem.convite@exemplo.test');

        for ($tentativa = 0; $tentativa < 3; $tentativa++) {
            $this->post('primeiro-acesso/token-adivinhado-' . $tentativa, [
                'senha'          => 'segredo123',
                'confirma_senha' => 'segredo123',
            ]);
        }

        $mulher = (new MulherModel())->find($mulherId);
        $this->assertNull($mulher['user_id'], 'Token inválido não pode criar conta.');
    }

    public function testTokenGravadoEmHashNoBanco(): void
    {
        $mulherId = $this->mulherSemConvite('Maria Token Hash', 'hash@exemplo.test');
        $convite  = (new ConviteMulherModel())->gerar($mulherId, 24);

        $model = new ConviteMulherModel();
        $linha = $model->where('id', $convite['id'])->first();

        $this->assertSame(64, strlen($convite['token']), 'O token precisa ter entropia suficiente.');
        $this->assertNotSame($convite['token'], $linha['token_hash'], 'O token em texto puro não pode ser gravado.');
        $this->assertSame(hash('sha256', $convite['token']), $linha['token_hash']);
    }

    // --- integração com o envio ---------------------------------------------

    public function testEmailContemLinkDePrimeiroAcesso(): void
    {
        $mulherId = $this->mulherSemConvite('Mariana Link', 'link@exemplo.test');
        $servico  = $this->emailServiceComRemetente();

        $convite = (new ConviteMulherModel())->gerar($mulherId, 24);
        $enviado = $servico->enviar(
            'link@exemplo.test',
            'Primeiro acesso',
            '<p>acesse</p>'
        );

        $this->assertTrue($enviado, 'Com remetente configurado o envio deve funcionar.');

        $email = service('email');
        $this->assertInstanceOf(MockEmail::class, $email);
    }

    public function testFalhaDoTransporteReportaOMotivoTecnico(): void
    {
        $this->emailServiceComRemetente();
        $mock = $this->mockarEmailRecusando();

        $servico = new \App\Libraries\EmailServico();
        $enviado = $servico->enviar('link@exemplo.test', 'Primeiro acesso', '<p>acesse</p>');

        $this->assertFalse($enviado);

        // O operador precisa do motivo real (535, TLS, timeout), não de um
        // "falhou" genérico que manda rever host, porta e senha à toa.
        $erro = (string) $servico->ultimoErro();

        $this->assertNotSame('', $erro);
        $this->assertStringContainsString('535-5.7.8 Username and Password not accepted', $erro);
        $this->assertStringNotContainsString('recusou ou não respondeu', $erro);
    }

    public function testFalhaDoTransporteNaoExpoeSenhaNemToken(): void
    {
        $this->emailServiceComRemetente();
        $this->mockarEmailRecusando();

        (new ConfiguracaoModel())->salvarCampos([
            'email_smtp_user' => 'conta@exemplo.test',
            'email_smtp_pass' => 'senha-que-nao-pode-vazar',
        ]);

        $servico = new \App\Libraries\EmailServico();
        $servico->enviar('link@exemplo.test', 'Primeiro acesso', '<p>acesse</p>');

        $erro = (string) $servico->ultimoErro();

        $this->assertStringNotContainsString('senha-que-nao-pode-vazar', $erro);
        $this->assertStringNotContainsString(base64_encode('senha-que-nao-pode-vazar'), $erro);
    }

    public function testEnvioParaPorta465UsaTlsImplicito(): void
    {
        $servico = $this->emailServiceComRemetente();

        (new ConfiguracaoModel())->salvarCampos([
            'email_smtp_host'   => 'smtp.gmail.com',
            'email_smtp_port'   => '465',
            'email_smtp_crypto' => 'tls',
        ]);

        $metodo = new ReflectionMethod($servico, 'configuracaoTransporte');
        $metodo->setAccessible(true);
        $config = $metodo->invoke($servico);

        // A 465 o TLS é implícito (RFC 8314): 'ssl' é obrigatório e o STARTTLS
        // seria um segundo upgrade sobre um canal já criptografado.
        $this->assertSame('smtp', $config['protocol']);
        $this->assertSame(465, $config['SMTPPort']);
        $this->assertSame('ssl', $config['SMTPCrypto']);

        // E o convite é HTML: enviar como texto puro perderia o botão do link.
        $this->assertSame('html', $config['mailType']);
    }

    public function testPorta587MantemStartTlsEscolhido(): void
    {
        $servico = $this->emailServiceComRemetente();

        (new ConfiguracaoModel())->salvarCampos([
            'email_smtp_host'   => 'smtp.gmail.com',
            'email_smtp_port'   => '587',
            'email_smtp_crypto' => 'tls',
        ]);

        $metodo = new ReflectionMethod($servico, 'configuracaoTransporte');
        $metodo->setAccessible(true);
        $config = $metodo->invoke($servico);

        $this->assertSame(587, $config['SMTPPort']);
        $this->assertSame('tls', $config['SMTPCrypto']);
        $this->assertTrue($config['SMTPAuth']);
    }

    public function testCriptografiaNenhumaEhRespeitada(): void
    {
        $servico = $this->emailServiceComRemetente();

        // "Nenhuma" é opção válida da tela (relay interno sem TLS) e precisa
        // continuar significando sem TLS, não virar tls por omissão.
        (new ConfiguracaoModel())->salvarCampos([
            'email_smtp_host'   => 'relay.interno.local',
            'email_smtp_port'   => '25',
            'email_smtp_crypto' => '',
        ]);

        $metodo = new ReflectionMethod($servico, 'configuracaoTransporte');
        $metodo->setAccessible(true);
        $config = $metodo->invoke($servico);

        $this->assertSame('smtp', $config['protocol']);
        $this->assertSame(25, $config['SMTPPort']);
        $this->assertSame('', $config['SMTPCrypto'], 'A escolha "Nenhuma" não pode virar tls.');
    }

    public function testCriptografiaNuncaGravadaAssumeTls(): void
    {
        $servico = $this->emailServiceComRemetente();

        (new ConfiguracaoModel())->salvarCampos([
            'email_smtp_host' => 'smtp.gmail.com',
            'email_smtp_port' => '587',
        ]);

        $modelo = new ConfiguracaoModel();
        $modelo->where('chave', 'email_smtp_crypto')->delete();

        $metodo = new ReflectionMethod($servico, 'configuracaoTransporte');
        $metodo->setAccessible(true);
        $config = $metodo->invoke($servico);

        $this->assertSame(587, $config['SMTPPort'], 'Sem porta gravada, a padrão segura é 587.');
        $this->assertSame('tls', $config['SMTPCrypto'], 'Sem escolha gravada, o padrão seguro é tls.');
    }

    public function testPhpSemOpensslFalhaAntesDeTocarORede(): void
    {
        $servico = $this->emailServiceComRemetente();

        (new ConfiguracaoModel())->salvarCampos([
            'email_smtp_host'   => 'smtp.gmail.com',
            'email_smtp_port'   => '587',
            'email_smtp_user'   => 'conta@exemplo.test',
            'email_smtp_pass'   => 'senha-secreta',
        ]);

        if ($servico->tlsSuportado()) {
            $this->markTestSkipped('O PHP deste ambiente tem openssl: a falha de ambiente não é reproduzível.');
        }

        $this->assertFalse($servico->enviar('link@exemplo.test', 'Assunto', '<p>acesse</p>'));

        // Sem openssl, o erro precisa dizer que o problema é o PHP, e não
        // induzir o operador a rever host, porta e credenciais.
        $erro = (string) $servico->ultimoErro();

        $this->assertStringContainsString('openssl', $erro);
        $this->assertStringNotContainsString('535', $erro);
    }

    // --- apoio ---------------------------------------------------------------

    /**
     * MockEmail que recusa a mensagem e registra uma recusa típica de SMTP,
     * para exercitar a leitura do diagnóstico pelo EmailServico.
     */
    private function mockarEmailRecusando(): MockEmail
    {
        $mock = new TransporteRecusadoEmail(config('Email'));
        $mock->returnValue = false;

        Services::injectMock('email', $mock);

        return $mock;
    }

    private function emailServiceComRemetente(): \App\Libraries\EmailServico
    {
        (new ConfiguracaoModel())->salvarCampos([
            'email_remetente_email' => 'procuradoria@exemplo.test',
            'email_remetente_nome'  => 'Procuradoria da Mulher',
        ]);

        return new \App\Libraries\EmailServico();
    }

    private function mulherSemConvite(string $nome, string $email): int
    {
        $modelo = new MulherModel();
        $modelo->skipValidation(true)->insert([
            'nome_completo' => $nome,
            'email'         => $email,
            'user_id'       => null,
        ]);

        return (int) $modelo->getInsertID();
    }

    /**
     * @return array{0: int, 1: string} id da mulher e token em texto puro
     */
    private function mulherComConviteValido(string $nome = 'Ana Convite'): array
    {
        $email = 'primeiro.acesso.' . bin2hex(random_bytes(4)) . '@exemplo.test';
        $id    = $this->mulherSemConvite($nome, $email);

        $convite = (new ConviteMulherModel())->gerar($id, 24);

        return [$id, $convite['token']];
    }

    private function ultimaMulherId(): int
    {
        return (int) (new MulherModel())->orderBy('id', 'DESC')->first()['id'];
    }

    private function userDaUltimaMulher(): ?array
    {
        $mulher = (new MulherModel())->find($this->ultimaMulherId());
        if ($mulher === null || empty($mulher['user_id'])) {
            return null;
        }

        return (new UserModel())->find((int) $mulher['user_id']);
    }

    private function emailDaUltimaMulher(): string
    {
        return (string) ((new MulherModel())->find($this->ultimaMulherId())['email'] ?? '');
    }

    /**
     * Sessão da administradora usando o sistema. Reproduz o ambiente real em
     * que o convite é aberto: o navegador da equipe está autenticado.
     *
     * @return array<string, mixed>
     */
    private function sessaoAdmin(): array
    {
        return [
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Administradora',
            'usuario_email' => 'admin@teste.com',
            'perfil'        => 'admin',
        ];
    }

    private function corromperEmailDaUltimaMulher(string $email): void
    {
        $modelo = new MulherModel();
        $modelo->skipValidation(true)->update($this->ultimaMulherId(), ['email' => $email]);
    }

    private function expirarConvite(): void
    {
        // Builder do model para o DBPrefix de teste (db_) ser aplicado.
        $modelo = new ConviteMulherModel();
        $modelo->set('expira_em', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->where('mulher_id', $this->ultimaMulherId())
            ->update();
    }

    private function utilizarConvite(): void
    {
        $modelo = new ConviteMulherModel();
        $modelo->set('utilizado_em', date('Y-m-d H:i:s'))
            ->where('mulher_id', $this->ultimaMulherId())
            ->update();
    }
}
