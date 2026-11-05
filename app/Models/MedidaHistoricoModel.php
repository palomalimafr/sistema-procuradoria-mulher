<?php

namespace App\Models;

use CodeIgniter\Model;

class MedidaHistoricoModel extends Model
{
    protected $table            = 'medida_historico';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'medida_id', 'acao', 'status_anterior', 'status_novo', 'observacao',
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
     * Ações registradas no histórico da medida.
     */
    public const ACOES = [
        'criacao'       => 'Medida registrada',
        'situacao'      => 'Situação atualizada',
        'descumprimento' => 'Descumprimento registrado',
    ];

    /**
     * Registra um evento relevante da medida (criação, situação,
     * descumprimento).
     */
    public function registrar(
        int $medidaId,
        string $acao,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
        ?int $usuarioId = null,
        ?string $observacao = null
    ): void {
        $this->insert([
            'medida_id'       => $medidaId,
            'acao'            => $acao,
            'status_anterior' => $statusAnterior,
            'status_novo'     => $statusNovo,
            'observacao'      => $observacao === null ? null : mb_substr($observacao, 0, 255),
            'usuario_id'      => $usuarioId,
        ]);
    }

    /**
     * Histórico de uma medida, do mais recente para o mais antigo.
     */
    public function listarPorMedida(int $medidaId): array
    {
        return $this->select('medida_historico.*, users.nome AS usuario_nome')
            ->join('users', 'users.id = medida_historico.usuario_id', 'left')
            ->where('medida_id', $medidaId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}