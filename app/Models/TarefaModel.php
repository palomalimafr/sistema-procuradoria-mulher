<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class TarefaModel extends Model
{
    protected $table            = 'tarefas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'titulo', 'descricao', 'mulher_id', 'atendimento_id', 'encaminhamento_id', 'caso_id',
        'acolhimento_id', 'compromisso_id', 'responsavel_id', 'prazo',
        'prioridade', 'status', 'data_conclusao',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Status simples da tarefa — a mudança ocorre sempre no MESMO registro.
     */
    public const STATUS = [
        'pendente'    => 'Pendente',
        'em_andamento' => 'Em andamento',
        'concluida'   => 'Concluída',
        'cancelada'   => 'Cancelada',
    ];

    /**
     * Transições permitidas na atualização de status.
     */
    public const FLUXO_STATUS = [
        'pendente'    => ['em_andamento', 'cancelada'],
        'em_andamento' => ['concluida', 'cancelada'],
        'concluida'   => [],
        'cancelada'   => [],
    ];

    /**
     * Status considerados ativos para a visão de tarefas atrasadas.
     */
    public const STATUS_ATIVOS = ['pendente', 'em_andamento'];

    protected $validationRules = [
        'titulo'          => 'required|max_length[200]',
        'mulher_id'       => 'permit_empty|integer',
        'atendimento_id'  => 'permit_empty|integer',
        'encaminhamento_id' => 'permit_empty|integer',
        'acolhimento_id'  => 'permit_empty|integer',
        'compromisso_id'  => 'permit_empty|integer',
        'responsavel_id'  => 'required|integer',
        'prazo'           => 'permit_empty|valid_date[Y-m-d]',
        'prioridade'      => 'required|in_list[emergencia,alta,media,baixa]',
        'status'          => 'required|in_list[pendente,em_andamento,concluida,cancelada]',
        'data_conclusao'  => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
    protected $validationMessages = [
        'titulo' => [
            'required' => 'Descreva a tarefa.',
        ],
        'responsavel_id' => [
            'required' => 'Informe o responsável pela tarefa.',
        ],
        'prioridade' => [
            'required' => 'Informe a prioridade da tarefa.',
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

        // Status via catálogo (o fluxo de transição continua fixo).
        $this->validationRules['status'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('status_tarefa')) . ']';
        $this->validationRules['prioridade'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('prioridade')) . ']';
    }

    /**
     * Prioridades reutilizando o catálogo "prioridade" (não duplicar lógica).
     */
    public static function prioridades(): array
    {
        return ConfiguracaoListaModel::opcoesCategoria('prioridade');
    }

    /**
     * Indica se o prazo já foi ultrapassado sem a tarefa ter sido concluída.
     */
    public static function estaAtrasada(array $tarefa, ?string $hoje = null): bool
    {
        if (empty($tarefa['prazo'])) {
            return false;
        }
        if (! in_array($tarefa['status'], self::STATUS_ATIVOS, true)) {
            return false;
        }

        return (string) $tarefa['prazo'] < ($hoje ?? date('Y-m-d'));
    }

    /**
     * Builder com mulher, atendimento, encaminhamento e responsável.
     */
    public function comDados()
    {
        return $this->select(
            'tarefas.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . 'encaminhamentos.orgao AS encaminhamento_orgao, '
            . 'encaminhamentos.servico AS encaminhamento_servico, '
            . 'users.nome AS responsavel_nome'
        )
            ->join('dados_mulher', 'dados_mulher.id = tarefas.mulher_id', 'left')
            ->join('atendimentos', 'atendimentos.id = tarefas.atendimento_id', 'left')
            ->join('encaminhamentos', 'encaminhamentos.id = tarefas.encaminhamento_id', 'left')
            ->join('users', 'users.id = tarefas.responsavel_id', 'left');
    }
}
