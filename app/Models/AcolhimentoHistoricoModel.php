<?php

namespace App\Models;

use CodeIgniter\Model;

class AcolhimentoHistoricoModel extends Model
{
    protected $table            = 'acolhimento_historico';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'acolhimento_id', 'status_anterior', 'status_novo', 'observacao', 'usuario_id',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = 'deleted_at';

    /**
     * Registra a mudança de status de um acolhimento.
     */
    public function registrar(
        int $acolhimentoId,
        ?string $statusAnterior,
        string $statusNovo,
        ?int $usuarioId = null,
        ?string $observacao = null
    ): void {
        $this->insert([
            'acolhimento_id'  => $acolhimentoId,
            'status_anterior' => $statusAnterior,
            'status_novo'     => $statusNovo,
            'observacao'      => $observacao,
            'usuario_id'      => $usuarioId,
        ]);
    }

    /**
     * Histórico de um acolhimento, do mais recente para o mais antigo.
     */
    public function listarPorAcolhimento(int $acolhimentoId): array
    {
        return $this->select('acolhimento_historico.*, users.nome AS usuario_nome')
            ->join('users', 'users.id = acolhimento_historico.usuario_id', 'left')
            ->where('acolhimento_id', $acolhimentoId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}