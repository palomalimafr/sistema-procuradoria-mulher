<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class MedidaProtetivaModel extends Model
{
    protected $table            = 'medidas_protetivas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mulher_id', 'atendimento_id', 'caso_id', 'possui_medida', 'numero',
        'data_solicitacao', 'data_concessao', 'orgao', 'processo',
        'situacao', 'data_validade', 'observacoes',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Situações previstas na especificação — a mudança ocorre sempre no MESMO
     * registro (não existe tabela separada por situação).
     */
    public const SITUACOES = [
        'solicitada' => 'Solicitada',
        'em_analise' => 'Em análise',
        'concedida'  => 'Concedida',
        'negada'     => 'Negada',
        'revogada'   => 'Revogada',
        'encerrada'  => 'Encerrada',
    ];

    /**
     * Situações em que a medida ainda está produzindo efeito: exatamente as
     * três não terminais de FLUXO_SITUACOES. "negada", "revogada" e
     * "encerrada" são estados finais e ficam de fora.
     */
    public const SITUACOES_ATIVAS = ['solicitada', 'em_analise', 'concedida'];

    /**
     * Transições permitidas na atualização de situação.
     */
    public const FLUXO_SITUACOES = [
        'solicitada' => ['em_analise'],
        'em_analise' => ['concedida', 'negada'],
        'concedida'  => ['revogada', 'encerrada'],
        'negada'     => [],
        'revogada'   => [],
        'encerrada'  => [],
    ];

    protected $validationRules = [
        'mulher_id'        => 'required|integer',
        'atendimento_id'   => 'permit_empty|integer',
        'possui_medida'    => 'permit_empty|in_list[0,1]',
        'numero'           => 'permit_empty|max_length[50]',
        'data_solicitacao' => 'permit_empty|valid_date[Y-m-d]',
        'data_concessao'   => 'permit_empty|valid_date[Y-m-d]',
        'processo'         => 'permit_empty|max_length[100]',
        'data_validade'    => 'permit_empty|valid_date[Y-m-d]',
        'observacoes'      => 'permit_empty',
    ];
    protected $validationMessages = [
        'mulher_id' => [
            'required' => 'Localize e selecione a mulher vinculada.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);

        // Órgão e situação vêm do catálogo de listas configuráveis.
        $this->validationRules['orgao'] = 'permit_empty|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('orgao')) . ']';
        $this->validationRules['situacao'] = 'permit_empty|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('situacao_medida')) . ']';
    }

    /**
     * Órgãos reutilizando o catálogo "orgao" (não duplicar cadastro).
     */
    public static function orgaosComChaves(): array
    {
        return ConfiguracaoListaModel::opcoesCategoria('orgao');
    }

    /**
     * Builder com mulher, atendimento e a quantidade de descumprimentos.
     */
    public function comDados()
    {
        // O subselect é escrito com o prefixo real das tabelas (ex.:
        // db_medida_descumprimentos nos testes), senão quebra em qualquer
        // ambiente com prefixo configurado.
        $descumprimentos = $this->db->prefixTable('medida_descumprimentos');
        $medidas         = $this->db->prefixTable('medidas_protetivas');

        return $this->select(
            'medidas_protetivas.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'dados_mulher.data_nascimento AS mulher_nascimento, '
            . 'dados_mulher.bairro AS mulher_bairro, '
            . 'dados_mulher.municipio AS mulher_municipio, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . "(SELECT COUNT(*) FROM {$descumprimentos} md WHERE md.medida_id = {$medidas}.id) AS qtd_descumprimentos"
        )
            ->join('dados_mulher', 'dados_mulher.id = medidas_protetivas.mulher_id')
            ->join('atendimentos', 'atendimentos.id = medidas_protetivas.atendimento_id', 'left');
    }
}
