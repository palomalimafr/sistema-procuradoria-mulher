<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuração do backup do banco de dados SQLite.
 *
 * Sobrescrevível pelo .env:
 *
 *   # Liga/desliga o backup automático
 *   BACKUP_ATIVO=true
 *   # Diretório dos backups (vazio = writable/backups)
 *   BACKUP_DIRETORIO=
 *   # Quantos backups manter (rotação automática)
 *   BACKUP_RETENCAO=10
 *   # Intervalo do backup automático em horas (0 = desativa o automático)
 *   BACKUP_INTERVALO_HORAS=24
 */
class Backup extends BaseConfig
{
    public bool $ativo = true;

    public string $diretorio = '';

    public int $retencao = 10;

    public int $intervaloHoras = 24;

    public string $prefixo = 'backup_cac';

    public function __construct()
    {
        parent::__construct();

        $this->ativo          = env('BACKUP_ATIVO', '1') === '1';
        $this->diretorio      = (string) env('BACKUP_DIRETORIO', '');
        $this->retencao       = max(1, (int) env('BACKUP_RETENCAO', '10'));
        $this->intervaloHoras = max(0, (int) env('BACKUP_INTERVALO_HORAS', '24'));
    }
}
