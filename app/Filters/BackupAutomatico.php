<?php

namespace App\Filters;

use App\Libraries\BackupBanco;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Dispara o backup automático do banco quando o intervalo configurado
 * foi atingido. A verificação é barata (stat de um arquivo); o backup
 * em si só roda quando necessário.
 */
class BackupAutomatico implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (ENVIRONMENT === 'testing') {
            return;
        }

        $backup = new BackupBanco();

        if ($backup->precisaBackup()) {
            $resultado = $backup->executar();

            if (! $resultado['ok']) {
                log_message('error', 'Backup automático falhou: ' . $resultado['erro']);
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
