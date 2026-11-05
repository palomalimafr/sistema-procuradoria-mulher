<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class CompromissoModel extends Model
{
    protected $table            = 'compromissos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mulher_id', 'atendimento_id', 'encaminhamento_id', 'acolhimento_id',
        'tipo', 'data', 'hora_inicio', 'hora_fim', 'profissional_id',
        'observacoes', 'status', 'reagendado_para_id',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Tipos de compromisso (base configurável — especificação da agenda).
     */
    public const TIPOS = [
        'retorno'                => 'Retorno',
        'atendimento_juridico'   => 'Atendimento jurídico',
        'atendimento_social'     => 'Atendimento social',
        'atendimento_psicologico' => 'Atendimento psicológico',
        'reuniao'                => 'Reunião',
        'encaminhamento'         => 'Encaminhamento',
        'visita'                 => 'Visita',
        'audiencia'              => 'Audiência',
        'outro'                  => 'Outro compromisso',
    ];

    /**
     * Status do compromisso (especificação da agenda).
     */
    public const STATUS = [
        'agendado'   => 'Agendado',
        'confirmado' => 'Confirmado',
        'realizado'  => 'Realizado',
        'faltou'     => 'Faltou',
        'cancelado'  => 'Cancelado',
        'reagendado' => 'Reagendado',
    ];

    /**
     * Transições permitidas na atualização simples de status. "Reagendado"
     * NÃO entra aqui: entrar em "Reagendado" exige a ação dedicada, que cria o
     * novo compromisso e mantém o original (rastreamento via reagendado_para_id).
     */
    public const FLUXO_STATUS = [
        'agendado'   => ['confirmado', 'cancelado'],
        'confirmado' => ['realizado', 'faltou', 'cancelado'],
        'faltou'     => ['cancelado'],
        'realizado'  => [],
        'cancelado'  => [],
        'reagendado' => [],
    ];

    /**
     * Status a partir dos quais o reagendamento (ação dedicada) é permitido.
     */
    public const REAGENDAR_DE = ['agendado', 'confirmado', 'faltou'];

    protected $validationRules = [
        'mulher_id'        => 'permit_empty|integer',
        'atendimento_id'   => 'permit_empty|integer',
        'encaminhamento_id' => 'permit_empty|integer',
        'acolhimento_id'   => 'permit_empty|integer',
        'tipo'             => 'required',
        'data'             => 'required|valid_date[Y-m-d]',
        'hora_inicio'      => 'required|valid_date[H:i:s]',
        'hora_fim'         => 'permit_empty|valid_date[H:i:s]',
        'profissional_id'  => 'permit_empty|integer',
        'status'           => 'required|in_list[agendado,confirmado,realizado,faltou,cancelado,reagendado]',
        'reagendado_para_id' => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        'tipo' => [
            'required' => 'Informe o tipo de compromisso.',
        ],
        'data' => [
            'required' => 'Informe a data do compromisso.',
        ],
        'hora_inicio' => [
            'required' => 'Informe o horário de início.',
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

        $this->validationRules['tipo'] = 'required|in_list[' . implode(',', array_keys(self::TIPOS)) . ']';
    }

    /**
     * Builder com mulher, atendimento, encaminhamento e profissional.
     */
    public function comDados()
    {
        return $this->select(
            'compromissos.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . 'encaminhamentos.orgao AS encaminhamento_orgao, '
            . 'encaminhamentos.servico AS encaminhamento_servico, '
            . 'users.nome AS profissional_nome'
        )
            ->join('dados_mulher', 'dados_mulher.id = compromissos.mulher_id', 'left')
            ->join('atendimentos', 'atendimentos.id = compromissos.atendimento_id', 'left')
            ->join('encaminhamentos', 'encaminhamentos.id = compromissos.encaminhamento_id', 'left')
            ->join('users', 'users.id = compromissos.profissional_id', 'left');
    }
}