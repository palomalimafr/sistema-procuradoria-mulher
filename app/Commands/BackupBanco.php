<?php

namespace App\Commands;

use App\Libraries\BackupBanco as BackupService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cria um backup do banco SQLite: php spark backup:banco
 */
class BackupBanco extends BaseCommand
{
    protected $group       = 'Banco de Dados';
    protected $name        = 'backup:banco';
    protected $description = 'Cria um backup do banco SQLite e rotaciona os arquivos antigos.';

    public function run(array $params)
    {
        $backup = new BackupService();
        $resultado = $backup->executar();

        if ($resultado['ok']) {
            CLI::write('Backup criado: ' . $resultado['arquivo'], 'green');
            CLI::write('Tamanho: ' . number_format($resultado['tamanho'] / 1024, 1, ',', '.') . ' KB');
        } else {
            CLI::write('Erro no backup: ' . $resultado['erro'], 'light_red');
        }

        CLI::write('Backups existentes: ' . count($backup->listar()) . ' (retenção: ' . $backup->getConfig()->retencao . ')');

        return EXIT_SUCCESS;
    }
}
