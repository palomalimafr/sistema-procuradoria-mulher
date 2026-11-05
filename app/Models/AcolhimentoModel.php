<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class AcolhimentoModel extends Model
{
    protected $table            = 'acolhimentos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'atendimento_id', 'data_hora', 'profissional_id', 'status',
        'motivo_procura', 'situacao_apresentada', 'necessidades_imediatas',
        'necessidade_protecao', 'necessidade_juridica', 'necessidade_psicologica',
        'necessidade_social', 'necessidade_saude', 'necessidade_seguranca',
        'necessidade_abrigo', 'outras_necessidades', 'risco_identificado',
        'observacoes', 'data_conclusao',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Status próprio do acolhimento (não confundir com o status do caso,
     * que é etapa futura).
     */
    public const STATUS = [
        'em_andamento' => 'Em andamento',
        'concluido'    => 'Concluído',
    ];

    public const RISCOS = [
        'baixo'   => 'Baixo',
        'medio'   => 'Médio',
        'alto'    => 'Alto',
        'extremo' => 'Extremo',
    ];

    protected $validationRules = [
        'atendimento_id' => 'required|integer',
        'data_hora'      => 'required',
        'profissional_id' => 'permit_empty|integer',
        'status'         => 'required|in_list[em_andamento,concluido]',
        'risco_identificado' => 'permit_empty|in_list[baixo,medio,alto,extremo]',
    ];
    protected $validationMessages = [
        'atendimento_id' => [
            'required' => 'Selecione o atendimento vinculado.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);

        // Status via catálogo; o risco identificado permanece lista fixa.
        $this->validationRules['status'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('status_acolhimento')) . ']';
        $this->validationRules['risco_identificado'] = 'permit_empty|in_list[' . implode(',', array_keys(self::RISCOS)) . ']';
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
     * Necessidades identificadas reutilizadas pelo formulário e pela ficha.
     * Campo => rótulo.
     */
    public function necessidades(): array
    {
        return [
            'necessidade_protecao'     => 'Proteção',
            'necessidade_juridica'     => 'Jurídica',
            'necessidade_psicologica'  => 'Psicológica',
            'necessidade_social'       => 'Social',
            'necessidade_saude'        => 'Saúde',
            'necessidade_seguranca'    => 'Segurança',
            'necessidade_abrigo'       => 'Abrigo',
            'outras'                   => 'Outras',
        ];
    }

    /**
     * Necessidades marcadas no registro (array de rótulos).
     */
    public function necessidadesMarcadas(array $acolhimento): array
    {
        $marcadas = [];
        foreach ($this->necessidades() as $campo => $rotulo) {
            if ($campo === 'outras') {
                if (! empty($acolhimento['outras_necessidades'])) {
                    $marcadas[$rotulo] = (string) $acolhimento['outras_necessidades'];
                }
                continue;
            }
            if (! empty($acolhimento[$campo])) {
                $marcadas[$rotulo] = '';
            }
        }

        return $marcadas;
    }

    /**
     * Builder com dados do atendimento, da mulher e do profissional.
     */
    public function comDados()
    {
        return $this->select(
            'acolhimentos.*, '
            . 'atendimentos.id AS atendimento_id, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . 'atendimentos.data_hora AS atendimento_data_hora, '
            . 'atendimentos.prioridade AS atendimento_prioridade, '
            . 'atendimentos.situacao AS atendimento_situacao, '
            . 'atendimentos.mulher_id AS mulher_id, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'dados_mulher.data_nascimento AS mulher_nascimento, '
            . 'dados_mulher.bairro AS mulher_bairro, '
            . 'dados_mulher.municipio AS mulher_municipio, '
            . 'users.nome AS profissional_nome'
        )
            ->join('atendimentos', 'atendimentos.id = acolhimentos.atendimento_id')
            ->join('dados_mulher', 'dados_mulher.id = atendimentos.mulher_id')
            ->join('users', 'users.id = acolhimentos.profissional_id', 'left');
    }
}