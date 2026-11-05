<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Histórico/auditoria do documento (mesmo padrão do processo/acolhimento).
 *
 * Registra upload, edição de metadados, alteração de sigilo, download e
 * exclusão. NUNCA é apagado quando o documento é excluído logicamente.
 */
class DocumentoHistoricoModel extends Model
{
    protected $table            = 'documento_historico';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'documento_id', 'acao', 'status_anterior', 'status_novo', 'observacao',
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
     * Ações registradas no histórico do documento.
     */
    public const ACOES = [
        'upload'    => 'Documento enviado',
        'edicao'    => 'Dados do documento editados',
        'sigilo'    => 'Classificação de sigilo alterada',
        'download'  => 'Documento acessado',
        'exclusao'  => 'Documento excluído',
    ];

    /**
     * Registra um evento relevante do documento (upload, edição, sigilo,
     * download ou exclusão).
     */
    public function registrar(
        int $documentoId,
        string $acao,
        ?string $statusAnterior = null,
        ?string $statusNovo = null,
        ?int $usuarioId = null,
        ?string $observacao = null
    ): void {
        $this->insert([
            'documento_id'     => $documentoId,
            'acao'             => $acao,
            'status_anterior'  => $statusAnterior,
            'status_novo'      => $statusNovo,
            'observacao'       => $observacao === null ? null : mb_substr($observacao, 0, 255),
            'usuario_id'       => $usuarioId,
        ]);
    }

    /**
     * Histórico de um documento, do mais recente para o mais antigo.
     */
    public function listarPorDocumento(int $documentoId): array
    {
        return $this->select('documento_historico.*, users.nome AS usuario_nome')
            ->join('users', 'users.id = documento_historico.usuario_id', 'left')
            ->where('documento_id', $documentoId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}