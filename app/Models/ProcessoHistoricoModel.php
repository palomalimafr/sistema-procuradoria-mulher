<?php

namespace App\Models;

use CodeIgniter\Model;

class ProcessoHistoricoModel extends Model
{
    protected $table            = 'processo_historico';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'processo_id', 'acao', 'status_anterior', 'status_novo', 'observacao',
        'usuario_id',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = 'deleted_at';

    /**
     * Ações registradas no histórico do processo.
     */
    public const ACOES = [
        'criacao'     => 'Processo registrado',
        'situacao'    => 'Situação atualizada',
        'responsavel' => 'Responsável atualizado',
        'edicao'      => 'Dados do processo editados',
    ];

    /**
     * Registra um evento relevante do processo (criação, situação,
     * responsável ou edição).
     */
    public function registrar(
        int $processoId,
        string $acao,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
        ?int $usuarioId = null,
        ?string $observacao = null
    ): void {
        $this->insert([
            'processo_id'      => $processoId,
            'acao'             => $acao,
            'status_anterior'  => $statusAnterior,
            'status_novo'      => $statusNovo,
            'observacao'       => $observacao === null ? null : mb_substr($observacao, 0, 255),
            'usuario_id'       => $usuarioId,
        ]);
    }

    /**
     * Histórico de um processo, do mais recente para o mais antigo.
     */
    public function listarPorProcesso(int $processoId): array
    {
        return $this->select('processo_historico.*, users.nome AS usuario_nome')
            ->join('users', 'users.id = processo_historico.usuario_id', 'left')
            ->where('processo_id', $processoId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}