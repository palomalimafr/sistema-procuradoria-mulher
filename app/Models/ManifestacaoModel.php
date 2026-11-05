<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

/**
 * Manifestações (módulo OUVIDORIA > Denúncias/Ouvidoria).
 *
 * A manifestação pode ser anônima ou registrar dados da pessoa (opcionais),
 * pode ser vinculada a uma mulher já cadastrada e pode ser encaminhada a uma
 * instituição da Rede de Proteção (rede_instituicoes — estrutura existente).
 * A prioridade reutiliza AtendimentoModel::PRIORIDADES.
 */
class ManifestacaoModel extends Model
{
    protected $table            = 'manifestacoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'protocolo', 'data_hora', 'tipo', 'origem', 'anonimo',
        'pessoa_nome', 'pessoa_cpf', 'pessoa_telefone', 'pessoa_email',
        'mulher_id', 'descricao', 'local_relacionado', 'observacoes',
        'prioridade', 'situacao', 'responsavel_id', 'instituicao_id',
        'data_encerramento',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Tipos de manifestação (lista simples e configurável).
     */
    public const TIPOS = [
        'denuncia'    => 'Denúncia',
        'reclamacao'  => 'Reclamação',
        'solicitacao' => 'Solicitação',
        'informacao'  => 'Informação',
        'elogio'      => 'Elogio',
        'sugestao'    => 'Sugestão',
        'outro'       => 'Outro',
    ];

    /**
     * Origem/canal pelo qual a manifestação foi recebida.
     */
    public const ORIGENS = [
        'presencial'   => 'Presencial',
        'telefone'     => 'Telefone',
        'whatsapp'     => 'WhatsApp',
        'email'        => 'E-mail',
        'site_sistema' => 'Site/Sistema',
        'outro'        => 'Outro',
    ];

    /**
     * Situações/status da manifestação (fluxo de acompanhamento).
     */
    public const SITUACOES = [
        'nova'               => 'Nova',
        'em_analise'         => 'Em análise',
        'em_atendimento'     => 'Em atendimento',
        'encaminhada'        => 'Encaminhada',
        'aguardando_retorno' => 'Aguardando retorno',
        'resolvida'          => 'Resolvida',
        'encerrada'          => 'Encerrada',
    ];

    /**
     * Situações que finalizam o acompanhamento (gravam data_encerramento).
     */
    public const SITUACOES_FINALIZADAS = [
        'resolvida', 'encerrada',
    ];

    /**
     * Transições permitidas a partir de cada situação.
     */
    public const FLUXO_STATUS = [
        'nova'               => ['em_analise', 'em_atendimento', 'encaminhada', 'resolvida', 'encerrada'],
        'em_analise'         => ['em_atendimento', 'encaminhada', 'resolvida', 'encerrada'],
        'em_atendimento'     => ['aguardando_retorno', 'encaminhada', 'resolvida', 'encerrada'],
        'encaminhada'        => ['aguardando_retorno', 'em_atendimento', 'resolvida', 'encerrada'],
        'aguardando_retorno' => ['em_atendimento', 'encaminhada', 'resolvida', 'encerrada'],
        'resolvida'          => ['encerrada'],
        'encerrada'          => [],
    ];

    protected $validationRules = [
        'protocolo'       => 'required|max_length[30]',
        'data_hora'       => 'required',
        'tipo'            => 'required|in_list[denuncia,reclamacao,solicitacao,informacao,elogio,sugestao,outro]',
        'origem'          => 'required|in_list[presencial,telefone,whatsapp,email,site_sistema,outro]',
        'anonimo'         => 'permit_empty|in_list[0,1]',
        'pessoa_nome'     => 'permit_empty|max_length[120]',
        'pessoa_cpf'      => 'permit_empty|max_length[20]',
        'pessoa_telefone' => 'permit_empty|max_length[30]',
        'pessoa_email'    => 'permit_empty|max_length[120]',
        'mulher_id'       => 'permit_empty|integer',
        'descricao'       => 'required',
        'local_relacionado' => 'permit_empty|max_length[255]',
        'prioridade'      => 'required|in_list[emergencia,alta,media,baixa]',
        'situacao'        => 'required|in_list[nova,em_analise,em_atendimento,encaminhada,aguardando_retorno,resolvida,encerrada]',
        'responsavel_id'  => 'permit_empty|integer',
        'instituicao_id'  => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        'descricao' => [
            'required' => 'Descreva a manifestação ou denúncia.',
        ],
        'protocolo' => [
            'required'   => 'O protocolo é obrigatório.',
            'max_length' => 'Protocolo muito longo.',
        ],
        'data_hora' => [
            'required' => 'A data/hora do registro é obrigatória.',
        ],
        'tipo' => [
            'required' => 'Informe o tipo da manifestação.',
        ],
        'origem' => [
            'required' => 'Informe a origem/canal da manifestação.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);

        // Tipo de manifestação permanece lista fixa (não faz parte das 15 categorias).
        $this->validationRules['tipo'] = 'required|in_list[' . implode(',', array_keys(self::TIPOS)) . ']';
        // Origem e situação vêm do catálogo de listas configuráveis.
        $this->validationRules['origem'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('origem_manifestacao')) . ']';
        $this->validationRules['situacao'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('situacao_manifestacao')) . ']';
        $this->validationRules['prioridade'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('prioridade')) . ']';
    }

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Prioridades reaproveitadas do atendimento (catálogo "prioridade").
     */
    public function prioridades(): array
    {
        return ConfiguracaoListaModel::opcoesCategoria('prioridade');
    }

    /**
     * Próximo protocolo único no formato MAN-AANN-NNNNNN (ex.: MAN-26-000001).
     */
    public function proximoProtocolo(?int $ano = null): string
    {
        $ano = $ano ?? (int) date('y');
        $prefixo = 'MAN-' . $ano . '-';

        $ultimo = $this->select('protocolo')
            ->like('protocolo', $prefixo, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $sequencia = 0;
        if ($ultimo !== null && preg_match('/^MAN-\d{2}-(\d+)$/', (string) $ultimo['protocolo'], $m) === 1) {
            $sequencia = (int) $m[1];
        }

        return sprintf('MAN-%s-%06d', $ano, $sequencia + 1);
    }

    /**
     * Builder com mulher vinculada, responsável e instituição de destino.
     */
    public function comDados()
    {
        return $this->select(
            'manifestacoes.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'users.nome AS responsavel_nome, '
            . 'rede_instituicoes.nome AS instituicao_nome, '
            . 'rede_instituicoes.municipio AS instituicao_municipio'
        )
            ->join('dados_mulher', 'dados_mulher.id = manifestacoes.mulher_id', 'left')
            ->join('users', 'users.id = manifestacoes.responsavel_id', 'left')
            ->join('rede_instituicoes', 'rede_instituicoes.id = manifestacoes.instituicao_id', 'left');
    }

    /**
     * Expressão SQL usada para ordenar pela urgência da prioridade.
     */
    public function ordemPrioridadeSql(): string
    {
        $tabela = $this->db->prefixTable('manifestacoes');

        return "CASE {$tabela}.prioridade WHEN 'emergencia' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END";
    }
}