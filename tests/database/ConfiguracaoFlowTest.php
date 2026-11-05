<?php

use App\Models\ConfiguracaoModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo SISTEMA > Configurações: acesso restrito ao admin,
 * persistência em `sistema_configuracoes` (chave -> valor), validação dos
 * campos institucionais e upload/remoção do logo (fora do docroot, no padrão
 * do módulo Documentos).
 *
 * Em CLI não há POST multipart real; o teste popula $_FILES manualmente e usa
 * service('superglobals')->setFilesArray() antes da requisição. O arquivo de
 * teste tem conteúdo PNG real para o detector de MIME (finfo).
 *
 * @internal
 */
final class ConfiguracaoFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    /** @var array<int, string> Caminhos temporários de arquivos de teste. */
    private array $tmpArquivos = [];

    /** @var array<int, string> Caminhos físicos criados no diretório de upload. */
    private array $arquivosCriados = [];

    protected function tearDown(): void
    {
        foreach ($this->arquivosCriados as $full) {
            if (is_file($full)) {
                @unlink($full);
            }
        }
        foreach ($this->tmpArquivos as $tmp) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
        $_FILES = [];
        service('superglobals')->setFilesArray([]);

        parent::tearDown();
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

    private function dadosValidos(array $alteracoes = []): array
    {
        return array_merge([
            'nome_sistema'      => 'Sistema de Gestão da Mulher',
            'nome_instituicao'  => 'Prefeitura Municipal de Campos',
            'nome_procuradoria' => 'Procuradoria da Mulher',
            'telefone'          => '(22) 99999-0000',
            'email'             => 'procuradoria@exemplo.gov.br',
            'endereco'          => 'Rua das Flores, 100',
            'municipio'         => 'Campos dos Goytacazes',
            'estado'            => 'RJ',
        ], $alteracoes);
    }

    /**
     * Cria um arquivo PNG mínimo válido em disco e popula $_FILES para simular
     * o upload multipart. Retorna o caminho temporário.
     */
    private function anexarPng(string $nome = 'logo_teste.png'): string
    {
        $conteudo = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true);
        $tmp = sys_get_temp_dir() . '/logo_teste_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($tmp, $conteudo);

        $_FILES['logo'] = [
            'name'      => $nome,
            'type'      => 'image/png',
            'tmp_name'  => $tmp,
            'error'     => 0,
            'size'      => strlen($conteudo),
            'full_path' => $tmp,
        ];
        service('superglobals')->setFilesArray($_FILES);

        $this->tmpArquivos[] = $tmp;

        return $tmp;
    }

    private function anexarArquivoInvalido(string $nome = 'logo_falso.png'): string
    {
        $tmp = sys_get_temp_dir() . '/logo_teste_' . bin2hex(random_bytes(6)) . '.png';
        file_put_contents($tmp, 'isto nao e uma imagem');

        $_FILES['logo'] = [
            'name'      => $nome,
            'type'      => 'text/plain',
            'tmp_name'  => $tmp,
            'error'     => 0,
            'size'      => 19,
            'full_path' => $tmp,
        ];
        service('superglobals')->setFilesArray($_FILES);

        $this->tmpArquivos[] = $tmp;

        return $tmp;
    }

    /**
     * Cria o registro logo no banco e o arquivo físico, como se já tivessem
     * sido salvos anteriormente. Retorna o caminho relativo criado.
     */
    private function semearLogo(): string
    {
        $subPasta = date('Y/m');
        $dir = WRITEPATH . 'uploads/logo/';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $relativo = 'logo/' . bin2hex(random_bytes(8)) . '.png';
        $full = WRITEPATH . 'uploads/' . $relativo;
        file_put_contents($full, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true));

        (new ConfiguracaoModel())->salvarCampos(['logo' => $relativo]);

        $this->arquivosCriados[] = $full;

        return $relativo;
    }

    private function totalConfiguracoes(): int
    {
        return (new ConfiguracaoModel())->countAllResults();
    }

    public function testVisitanteNaoAcessaConfiguracoes(): void
    {
        $pagina = $this->get('sistema/configuracoes');
        $pagina->assertRedirectTo('/login');

        $salvar = $this->post('sistema/configuracoes', $this->dadosValidos());
        $salvar->assertRedirectTo('/login');
    }

    public function testFuncionarioNaoAcessaConfiguracoes(): void
    {
        $this->logarComo('funcionario');

        $result = $this->get('sistema/configuracoes');
        $result->assertRedirectTo('/dashboard');

        $salvar = $this->post('sistema/configuracoes', $this->dadosValidos());
        $salvar->assertRedirectTo('/dashboard');
    }

    public function testAdminAcessaPaginaDeConfiguracoes(): void
    {
        $this->logarComo('admin');

        $result = $this->get('sistema/configuracoes');

        $result->assertStatus(200);
        $texto = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Configurações', $texto);
        $this->assertStringContainsString('Informações institucionais', $texto);
        $this->assertStringContainsString('Nome da Procuradoria da Mulher', $texto);
        $this->assertStringContainsString('Logo', $texto);
        $this->assertStringContainsString('Envio de e-mail', $texto);
        $this->assertStringContainsString('Servidor SMTP', $texto);
        $this->assertStringContainsString('Validade do convite', $texto);
        $this->assertStringContainsString('Procuradoria da Mulher', $texto);
    }

    public function testAdminSalvaConfiguracoesEPersiste(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', $this->dadosValidos());

        $result->assertRedirectTo('/sistema/configuracoes');

        $model = new ConfiguracaoModel();
        $this->assertSame('Sistema de Gestão da Mulher', $model->obter('nome_sistema'));
        $this->assertSame('Prefeitura Municipal de Campos', $model->obter('nome_instituicao'));
        $this->assertSame('Procuradoria da Mulher', $model->obter('nome_procuradoria'));
        $this->assertSame('(22) 99999-0000', $model->obter('telefone'));
        $this->assertSame('procuradoria@exemplo.gov.br', $model->obter('email'));
        $this->assertSame('Rua das Flores, 100', $model->obter('endereco'));
        $this->assertSame('Campos dos Goytacazes', $model->obter('municipio'));
        $this->assertSame('RJ', $model->obter('estado'));
        // 15 das 16 chaves: a senha do SMTP em branco não é gravada, para não
        // destruir uma credencial que não veio no formulário.
        $this->assertSame(15, $this->totalConfiguracoes());
    }

    public function testAdminSalvaConfiguracoesDeEmailDoConvite(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', $this->dadosValidos() + [
            'email_smtp_host'              => 'smtp.exemplo.gov.br',
            'email_smtp_port'              => '587',
            'email_smtp_user'              => 'convites@exemplo.gov.br',
            'email_smtp_pass'              => 'senha-secreta',
            'email_smtp_crypto'            => 'tls',
            'email_remetente_email'        => 'convites@exemplo.gov.br',
            'email_remetente_nome'         => 'Procuradoria da Mulher',
            'email_convite_validade_horas' => '48',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes');

        $model = new ConfiguracaoModel();
        $this->assertSame('smtp.exemplo.gov.br', $model->obter('email_smtp_host'));
        $this->assertSame('587', $model->obter('email_smtp_port'));
        $this->assertSame('convites@exemplo.gov.br', $model->obter('email_smtp_user'));
        $this->assertSame('senha-secreta', $model->obter('email_smtp_pass'));
        $this->assertSame('tls', $model->obter('email_smtp_crypto'));
        $this->assertSame('convites@exemplo.gov.br', $model->obter('email_remetente_email'));
        $this->assertSame('Procuradoria da Mulher', $model->obter('email_remetente_nome'));
        $this->assertSame('48', $model->obter('email_convite_validade_horas'));
    }

    public function testSenhaSmtpEmBrancoMantemASenhaCadastrada(): void
    {
        $this->logarComo('admin');

        $this->post('sistema/configuracoes', $this->dadosValidos() + [
            'email_smtp_host' => 'smtp.exemplo.gov.br',
            'email_smtp_user' => 'convites@exemplo.gov.br',
            'email_smtp_pass' => 'senha-secreta',
        ]);

        // Salva de novo mudando outro campo e deixando a senha em branco: a
        // credencial não pode ser apagada sem querer.
        $result = $this->post('sistema/configuracoes', array_merge($this->dadosValidos(), [
            'email_smtp_host' => 'smtp.exemplo.gov.br',
            'email_smtp_user' => 'convites@exemplo.gov.br',
            'email_smtp_pass' => '',
            'nome_sistema'    => 'Nome Alterado',
        ]));

        $result->assertRedirectTo('/sistema/configuracoes');

        $model = new ConfiguracaoModel();
        $this->assertSame('senha-secreta', $model->obter('email_smtp_pass'), 'Senha do SMTP não pode ser apagada por campo em branco.');
        $this->assertSame('Nome Alterado', $model->obter('nome_sistema'));
    }

    public function testSenhaSmtpNaoApareceNaTela(): void
    {
        $this->logarComo('admin');

        $this->post('sistema/configuracoes', $this->dadosValidos() + [
            'email_smtp_host' => 'smtp.exemplo.gov.br',
            'email_smtp_user' => 'convites@exemplo.gov.br',
            'email_smtp_pass' => 'senha-secreta',
        ]);

        $result = $this->get('sistema/configuracoes');

        $result->assertStatus(200);
        $this->assertStringNotContainsString('senha-secreta', $result->getBody(), 'A senha do SMTP não pode ser reexibida no formulário.');
    }

    public function testPortaSmtpInvalidaEeRejeitada(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', $this->dadosValidos() + ['email_smtp_port' => 'abc']);

        $result->assertRedirect('/sistema/configuracoes');
        $this->assertSame('Sistema de Gestão', (new ConfiguracaoModel())->obter('nome_sistema', 'Sistema de Gestão'));
    }

    public function testValidadeDoConviteInvalidaEeRejeitada(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', $this->dadosValidos() + [
            'email_convite_validade_horas' => '99999999',
        ]);

        $result->assertRedirect('/sistema/configuracoes');
        $this->assertNull((new ConfiguracaoModel())->obter('email_convite_validade_horas'));
    }

    public function testConfiguracoesContinuamDisponiveisAposRecarregar(): void
    {
        $this->logarComo('admin');

        $this->post('sistema/configuracoes', $this->dadosValidos());

        $result = $this->get('sistema/configuracoes');

        $result->assertStatus(200);
        $texto = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Sistema de Gestão da Mulher', $texto);
        $this->assertStringContainsString('Prefeitura Municipal de Campos', $texto);
        $this->assertStringContainsString('Rua das Flores, 100', $texto);
    }

    public function testCampoEstadoEhValidado(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', $this->dadosValidos(['estado' => 'XX']));

        $result->assertRedirectTo('/sistema/configuracoes');
        $this->assertSame(0, $this->totalConfiguracoes());
    }

    public function testEmailInvalidoBloqueiaSalvamento(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', $this->dadosValidos(['email' => 'nao-e-email']));

        $result->assertRedirectTo('/sistema/configuracoes');
        $this->assertSame(0, $this->totalConfiguracoes());
    }

    public function testCamposObrigatoriosImpedemSalvamento(): void
    {
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', [
            'nome_sistema'      => '',
            'nome_instituicao'  => '',
            'nome_procuradoria' => '',
        ]);

        $result->assertRedirectTo('/sistema/configuracoes');
        $this->assertSame(0, $this->totalConfiguracoes());
    }

    public function testAdminEnviaLogoEPersistidoComArquivoFisico(): void
    {
        $this->logarComo('admin');
        $this->anexarPng();

        $result = $this->post('sistema/configuracoes', $this->dadosValidos());

        $result->assertRedirectTo('/sistema/configuracoes');

        $relativo = (new ConfiguracaoModel())->obter('logo');
        $this->assertNotNull($relativo);
        $this->assertStringStartsWith('logo/', $relativo);

        $full = WRITEPATH . 'uploads/' . $relativo;
        $this->assertFileExists($full);
        $this->arquivosCriados[] = $full;
    }

    public function testLogoEhServidoParaUsuarioAutenticado(): void
    {
        $this->logarComo('admin');
        $this->anexarPng();
        $this->post('sistema/configuracoes', $this->dadosValidos());

        $relativo = (new ConfiguracaoModel())->obter('logo');
        $this->arquivosCriados[] = WRITEPATH . 'uploads/' . $relativo;

        $this->logarComo('funcionario');

        $logo = $this->get('sistema/logo');

        $logo->assertStatus(200);
        $this->assertStringContainsString('image/png', $logo->response()->getHeaderLine('Content-Type'));
    }

    public function testLogoInvalidoEeRejeitadoSemPersistir(): void
    {
        $this->logarComo('admin');
        $this->anexarArquivoInvalido();

        $result = $this->post('sistema/configuracoes', $this->dadosValidos());

        $result->assertRedirectTo('/sistema/configuracoes');
        $this->assertNull((new ConfiguracaoModel())->obter('logo'));
        $this->assertSame(0, $this->totalConfiguracoes());
    }

    public function testRecarregarSemNovoLogoPreservaLogoCadastrado(): void
    {
        $relativo = $this->semearLogo();
        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes', $this->dadosValidos([
            'nome_sistema' => 'Novo Nome do Sistema',
        ]));

        $result->assertRedirectTo('/sistema/configuracoes');

        $model = new ConfiguracaoModel();
        $this->assertSame($relativo, $model->obter('logo'), 'Salvar outros campos não pode apagar o logo.');
        $this->assertSame('Novo Nome do Sistema', $model->obter('nome_sistema'));
    }

    public function testRemoverLogoApagaRegistroEArquivoFisico(): void
    {
        $relativo = $this->semearLogo();
        $full = WRITEPATH . 'uploads/' . $relativo;
        $this->assertFileExists($full);

        $this->logarComo('admin');

        $result = $this->post('sistema/configuracoes/remover-logo');

        $result->assertRedirectTo('/sistema/configuracoes');
        $this->assertNull((new ConfiguracaoModel())->obter('logo'));
        $this->assertFileDoesNotExist($full);
    }
}