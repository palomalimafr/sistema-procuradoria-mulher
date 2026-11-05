<?php

namespace App\Models;

use CodeIgniter\Model;

class ManifestacaoHistoricoModel extends Model
{
    protected $table            = 'manifestacao_historico';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'manifestacao_id', 'acao', 'status_anterior', 'status_novo', 'observacao',
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
     * Ações registradas no histórico da manifestação.
     */
    public const ACOES = [
        'criacao'       => 'Manifestação registrada',
        'situacao'      => 'Situação atualizada',
        'encaminhamento' => 'Encaminhada para a rede de proteção',
        'responsavel'   => 'Responsável atualizado',
        'edicao'        => 'Dados da manifestação editados',
    ];

    /**
     * Registra um evento relevante da manifestação (criação, situação,
     * encaminhamento, responsável ou edição).
     */
    public function registrar(
        int $manifestacaoId,
        string $acao,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
        ?int $usuarioId = null,
        ?string $observacao = null
    ): void {
        $this->insert([
            'manifestacao_id'  => $manifestacaoId,
            'acao'             => $acao,
            'status_anterior'  => $statusAnterior,
            'status_novo'      => $statusNovo,
            'observacao'       => $observacao === null ? null : mb_substr($observacao, 0, 255),
            'usuario_id'       => $usuarioId,
        ]);
    }

    /**
     * Histórico de uma manifestação, do mais recente para o mais antigo.
     */
    public function listarPorManifestacao(int $manifestacaoId): array
    {
        return $this->select('manifestacao_historico.*, users.nome AS usuario_nome')
            ->join('users', 'users.id = manifestacao_historico.usuario_id', 'left')
            ->where('manifestacao_id', $manifestacaoId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}