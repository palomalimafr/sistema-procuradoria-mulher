<?php

namespace App\Libraries;

use Config\Backup;
use Config\Database;

/**
 * Backup do banco SQLite usando VACUUM INTO (snapshot consistente,
 * seguro mesmo com o banco em uso).
 */
class BackupBanco
{
    protected Backup $config;

    /**
     * Caminho opcional do banco a copiar (útil em testes).
     * Quando nulo, é resolvido a partir da conexão ativa.
     */
    protected ?string $fonte = null;

    public function __construct(?Backup $config = null, ?string $fonte = null)
    {
        $this->config = $config ?? config('Backup');
        $this->fonte  = $fonte;
    }

    public function getConfig(): Backup
    {
        return $this->config;
    }

    /**
     * Caminho do diretório de backups (criado se necessário).
     */
    public function diretorio(): string
    {
        $dir = $this->config->diretorio !== ''
            ? rtrim($this->config->diretorio, '/\\')
            : WRITEPATH . 'backups';

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Lista os backups existentes, do mais recente para o mais antigo.
     *
     * @return list<array<string, mixed>>
     */
    public function listar(): array
    {
        $padrao = $this->diretorio() . $this->config->prefixo . '_*.db';
        $arquivos = glob($padrao) ?: [];

        $backups = [];
        foreach ($arquivos as $arquivo) {
            if (! is_file($arquivo)) {
                continue;
            }

            $backups[] = [
                'nome'     => basename($arquivo),
                'caminho'  => $arquivo,
                'tamanho'  => filesize($arquivo),
                'data'     => filemtime($arquivo),
            ];
        }

        usort($backups, static fn ($a, $b) => $b['data'] <=> $a['data']);

        return $backups;
    }

    /**
     * Informações do backup mais recente (ou null se não houver).
     *
     * @return array<string, mixed>|null
     */
    public function ultimoBackup(): ?array
    {
        $backups = $this->listar();

        return $backups[0] ?? null;
    }

    /**
     * Indica se já é hora de fazer um backup automático.
     */
    public function precisaBackup(): bool
    {
        if (! $this->config->ativo || $this->config->intervaloHoras <= 0) {
            return false;
        }

        $ultimo = $this->ultimoBackup();
        if ($ultimo === null) {
            return true;
        }

        $limite = time() - ($this->config->intervaloHoras * 3600);

        return (int) $ultimo['data'] < $limite;
    }

    /**
     * Executa o backup e a rotação dos arquivos antigos.
     *
     * @return array{ok: bool, arquivo: string, tamanho: int, erro: string}
     */
    public function executar(): array
    {
        $nome = $this->config->prefixo . '_' . date('Y-m-d_His') . '.db';
        $destino = $this->diretorio() . $nome;

        try {
            $fonte = $this->resolverCaminhoBanco();

            $pdo = new \PDO('sqlite:' . $fonte);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec('VACUUM INTO ' . $pdo->quote($destino));

            if (! is_file($destino)) {
                throw new \RuntimeException('O arquivo de backup não foi criado.');
            }

            $tamanho = filesize($destino);
            $removidos = $this->limparAntigos();

            log_message('info', "Backup criado: {$nome} ({$tamanho} bytes, {$removidos} removido(s) na rotação).");

            return [
                'ok'      => true,
                'arquivo' => $nome,
                'tamanho' => $tamanho,
                'erro'    => '',
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Falha no backup do banco: ' . $e->getMessage());

            return [
                'ok'      => false,
                'arquivo' => '',
                'tamanho' => 0,
                'erro'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove os backups mais antigos além da retenção configurada.
     */
    public function limparAntigos(): int
    {
        $backups = $this->listar();
        $removidos = 0;

        foreach (array_slice($backups, $this->config->retencao) as $antigo) {
            if (@unlink($antigo['caminho'])) {
                $removidos++;
            }
        }

        return $removidos;
    }

    /**
     * Valida o nome de um backup e retorna o caminho completo,
     * ou null se não existir/pertencer ao diretório de backups.
     */
    public function caminhoParaDownload(string $nome): ?string
    {
        $base = basename($nome);

        if ($base !== $nome || $base === '' || str_contains($base, '..')) {
            return null;
        }

        if (! str_starts_with($base, $this->config->prefixo . '_') || ! str_ends_with($base, '.db')) {
            return null;
        }

        $caminho = $this->diretorio() . $base;

        return is_file($caminho) ? $caminho : null;
    }

    /**
     * Caminho do arquivo do banco em uso.
     */
    protected function resolverCaminhoBanco(): string
    {
        if ($this->fonte !== null && $this->fonte !== '') {
            if (! is_file($this->fonte)) {
                throw new \RuntimeException("Arquivo do banco não encontrado: {$this->fonte}");
            }

            return $this->fonte;
        }

        $banco = Database::connect()->getDatabase();

        if ($banco === ':memory:' || str_starts_with($banco, ':memory:')) {
            throw new \RuntimeException('Banco em memória não pode ser copiado.');
        }

        if (is_file($banco) || str_starts_with($banco, '/')) {
            return $banco;
        }

        $candidato = WRITEPATH . $banco;

        if (is_file($candidato)) {
            return $candidato;
        }

        throw new \RuntimeException("Arquivo do banco não encontrado: {$banco}");
    }
}
