<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class EncaminhamentoModel extends Model
{
    protected $table            = 'encaminhamentos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mulher_id', 'atendimento_id', 'acolhimento_id', 'caso_id', 'data_hora',
        'profissional_id', 'status', 'orgao', 'servico', 'instituicao_id',
        'motivo', 'prioridade', 'prazo', 'observacoes', 'data_conclusao',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Status do encaminhamento (especificação da rede de proteção).
     */
    public const STATUS = [
        'pendente'          => 'Pendente',
        'encaminhado'       => 'Encaminhado',
        'em_atendimento'    => 'Em atendimento',
        'aguardando_retorno' => 'Aguardando retorno',
        'concluido'         => 'Concluído',
        'cancelado'         => 'Cancelado',
    ];

    /**
     * Status que indicam encaminhamento ainda em acompanhamento.
     */
    public const STATUS_PENDENTES = [
        'pendente', 'encaminhado', 'em_atendimento', 'aguardando_retorno',
    ];

    /**
     * Transitórios permitidos a partir de cada status (fluxo da especificação).
     * "Cancelado" é permitido em qualquer status em acompanhamento.
     */
    public const FLUXO_STATUS = [
        'pendente'           => ['encaminhado', 'cancelado'],
        'encaminhado'        => ['em_atendimento', 'cancelado'],
        'em_atendimento'     => ['aguardando_retorno', 'concluido', 'cancelado'],
        'aguardando_retorno' => ['concluido', 'cancelado'],
        'concluido'          => [],
        'cancelado'          => [],
    ];

    /**
     * Órgãos da rede de proteção (opções configuráveis — estrutura mínima).
     * O cadastro completo da Rede de Proteção fica para o módulo próprio.
     */
    public const ORGAOS = [
        'cras'                   => 'CRAS',
        'creas'                  => 'CREAS',
        'delegacia'              => 'Delegacia',
        'delegacia_mulher'       => 'Delegacia da Mulher',
        'policia_militar'        => 'Polícia Militar',
        'ministerio_publico'     => 'Ministério Público',
        'defensoria_publica'     => 'Defensoria Pública',
        'poder_judiciario'       => 'Poder Judiciário',
        'secretaria_saude'       => 'Secretaria de Saúde',
        'secretaria_assistencia' => 'Secretaria de Assistência Social',
        'caps'                   => 'CAPS',
        'ubs'                    => 'Unidade Básica de Saúde',
        'hospital'               => 'Hospital',
        'conselho_tutelar'       => 'Conselho Tutelar',
        'abrigo'                 => 'Abrigo',
        'outros'                 => 'Outros',
    ];

    /**
     * Serviços acessados na rede (opções configuráveis — estrutura mínima).
     */
    public const SERVICOS = [
        'psicologico'          => 'Serviço psicológico',
        'juridico'             => 'Serviço jurídico',
        'social'               => 'Atendimento social',
        'medico'               => 'Atendimento médico',
        'psicossocial'         => 'Apoio psicossocial',
        'abrigamento'          => 'Abrigamento',
        'orientacao_juridica'  => 'Orientação jurídica',
        'protecao'             => 'Proteção/segurança',
        'outros'               => 'Outros',
    ];

    protected $validationRules = [
        'mulher_id'      => 'required|integer',
        'atendimento_id' => 'permit_empty|integer',
        'acolhimento_id' => 'permit_empty|integer',
        'data_hora'      => 'required',
        'profissional_id' => 'permit_empty|integer',
        'status'         => 'required|in_list[pendente,encaminhado,em_atendimento,aguardando_retorno,concluido,cancelado]',
        'orgao'          => 'required|max_length[60]',
        'servico'        => 'permit_empty',
        'instituicao_id' => 'permit_empty|integer',
        'motivo'         => 'permit_empty',
        'prioridade'     => 'permit_empty|in_list[emergencia,alta,media,baixa]',
        'prazo'          => 'permit_empty|valid_date[Y-m-d]',
    ];
    protected $validationMessages = [
        'mulher_id' => [
            'required' => 'Localize e selecione a mulher vinculada.',
        ],
        'orgao' => [
            'required' => 'Informe o órgão da rede de proteção.',
        ],
        'data_hora' => [
            'required' => 'Informe a data do encaminhamento.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);

        // Listas configuráveis via catálogo (com fallback para as constantes).
        // O "orgao" do encaminhamento guarda o NOME da instituição (livre),
        // então não recebe in_list aqui — apenas a lista para exibição/filtro.
        $this->validationRules['status'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('status_encaminhamento')) . ']';
        $this->validationRules['prioridade'] = 'permit_empty|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('prioridade')) . ']';
        $this->validationRules['servico'] = 'permit_empty|in_list[' . implode(',', array_keys(self::SERVICOS)) . ']';
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
     * Builder com mulher e, quando houver, atendimento/acolhimento/profissional.
     */
    public function comDados()
    {
        return $this->select(
            'encaminhamentos.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'dados_mulher.data_nascimento AS mulher_nascimento, '
            . 'dados_mulher.bairro AS mulher_bairro, '
            . 'dados_mulher.municipio AS mulher_municipio, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . 'atendimentos.prioridade AS atendimento_prioridade, '
            . 'atendimentos.situacao AS atendimento_situacao, '
            . 'users.nome AS profissional_nome, '
            . 'acolhimentos.data_hora AS acolhimento_data_hora, '
            . 'rede_instituicoes.nome AS instituicao_nome'
        )
            ->join('dados_mulher', 'dados_mulher.id = encaminhamentos.mulher_id')
            ->join('atendimentos', 'atendimentos.id = encaminhamentos.atendimento_id', 'left')
            ->join('acolhimentos', 'acolhimentos.id = encaminhamentos.acolhimento_id', 'left')
            ->join('users', 'users.id = encaminhamentos.profissional_id', 'left')
            ->join('rede_instituicoes', 'rede_instituicoes.id = encaminhamentos.instituicao_id', 'left');
    }
}
