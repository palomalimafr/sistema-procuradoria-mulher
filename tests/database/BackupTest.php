<?php

use App\Libraries\BackupBanco;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Testes do backup do banco SQLite.
 *
 * @internal
 */
final class BackupTest extends CIUnitTestCase
{
    private string $dirTeste;
    private string $bancoFonte;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dirTeste = sys_get_temp_dir() . '/backup_cac_test_' . bin2hex(random_bytes(4));
        mkdir($this->dirTeste, 0775, true);

        // Banco de origem com dados
        $this->bancoFonte = $this->dirTeste . '/fonte.db';
        $pdo = new PDO('sqlite:' . $this->bancoFonte);
        $pdo->exec('CREATE TABLE usuarios (id INTEGER PRIMARY KEY, nome TEXT)');
        $pdo->exec("INSERT INTO usuarios (nome) VALUES ('Ana'), ('Pedro')");
        $pdo = null;
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dirTeste . '/*') ?: [] as $arquivo) {
            @unlink($arquivo);
        }
        @rmdir($this->dirTeste);

        parent::tearDown();
    }

    private function criarConfig(int $retencao = 10, int $intervalo = 24, bool $ativo = true): \Config\Backup
    {
        $config = new \Config\Backup();
        $config->ativo          = $ativo;
        $config->diretorio      = $this->dirTeste;
        $config->retencao       = $retencao;
        $config->intervaloHoras = $intervalo;

        return $config;
    }

    private function criarBackupFake(string $nome): void
    {
        file_put_contents($this->dirTeste . '/' . $nome, 'fake');
        touch($this->dirTeste . '/' . $nome, time() - 100000);
    }

    public function testExecutarCriaBackupValido(): void
    {
        $backup = new BackupBanco($this->criarConfig(), $this->bancoFonte);

        $resultado = $backup->executar();

        $this->assertTrue($resultado['ok']);
        $this->assertNotSame('', $resultado['arquivo']);
        $this->assertFileExists($this->dirTeste . '/' . $resultado['arquivo']);

        // O backup deve conter os mesmos dados
        $pdo = new PDO('sqlite:' . $this->dirTeste . '/' . $resultado['arquivo']);
        $this->assertSame(2, (int) $pdo->query('SELECT count(*) FROM usuarios')->fetchColumn());
    }

    public function testExecutarFalhaComBancoInexistente(): void
    {
        $backup = new BackupBanco($this->criarConfig(), $this->dirTeste . '/nao-existe.db');

        $resultado = $backup->executar();

        $this->assertFalse($resultado['ok']);
        $this->assertNotSame('', $resultado['erro']);
    }

    public function testRotacaoMantemSomenteRetencao(): void
    {
        // 4 backups antigos + 1 novo = 5; retenção 3 → sobram 3
        foreach (['backup_cac_2026-01-01_000000.db', 'backup_cac_2026-02-01_000000.db', 'backup_cac_2026-03-01_000000.db', 'backup_cac_2026-04-01_000000.db'] as $fake) {
            $this->criarBackupFake($fake);
        }

        $backup = new BackupBanco($this->criarConfig(retencao: 3), $this->bancoFonte);
        $backup->executar();

        $this->assertCount(3, $backup->listar());
    }

    public function testPrecisaBackup(): void
    {
        $config = $this->criarConfig(intervalo: 24);

        // Sem backups → precisa
        $backup = new BackupBanco($config, $this->bancoFonte);
        $this->assertTrue($backup->precisaBackup());

        // Após um backup recente → não precisa
        $backup->executar();
        $this->assertFalse($backup->precisaBackup());

        // Intervalo 0 desativa o automático
        $config2 = $this->criarConfig(intervalo: 0);
        $this->assertFalse((new BackupBanco($config2, $this->bancoFonte))->precisaBackup());

        // Desativado → nunca precisa
        $config3 = $this->criarConfig(ativo: false);
        $this->assertFalse((new BackupBanco($config3, $this->bancoFonte))->precisaBackup());
    }

    public function testCaminhoParaDownloadValidaNome(): void
    {
        $this->criarBackupFake('backup_cac_2026-08-14_120000.db');
        $backup = new BackupBanco($this->criarConfig(), $this->bancoFonte);

        $this->assertNotNull($backup->caminhoParaDownload('backup_cac_2026-08-14_120000.db'));
        $this->assertNull($backup->caminhoParaDownload('../outro.db'));
        $this->assertNull($backup->caminhoParaDownload('nao-existe.db'));
        $this->assertNull($backup->caminhoParaDownload('backup_outro_2026-08-14_120000.db'));
    }
}
