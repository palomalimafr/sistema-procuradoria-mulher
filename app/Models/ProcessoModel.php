<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class ProcessoModel extends Model
{
    protected $table            = 'processos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mulher_id', 'atendimento_id', 'medida_protetiva_id', 'caso_id', 'numero_processo',
        'tipo', 'orgao', 'vara', 'comarca', 'data', 'situacao',
        'responsavel_id', 'observacoes',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Tipos simples/configuráveis — sem lista extensa nesta etapa. A lista
     * poderá virar configuração futuramente.
     */
    public const TIPOS = [
        'outros' => 'Outros',
    ];

    /**
     * Situações genéricas e coerentes, sem inventar fluxo jurídico complexo.
     */
    public const SITUACOES = [
        'em_andamento' => 'Em andamento',
        'suspenso'     => 'Suspenso',
        'encerrado'    => 'Encerrado',
    ];

    protected $validationRules = [
        'mulher_id'           => 'required|integer',
        'atendimento_id'      => 'permit_empty|integer',
        'medida_protetiva_id' => 'permit_empty|integer',
        'numero_processo'     => 'permit_empty|max_length[100]',
        'vara'                => 'permit_empty|max_length[100]',
        'comarca'             => 'permit_empty|max_length[100]',
        'data'                => 'permit_empty|valid_date[Y-m-d]',
        'responsavel_id'      => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        'mulher_id' => [
            'required' => 'Localize e selecione a mulher vinculada.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);

        // Tipo do processo permanece lista fixa mínima.
        $this->validationRules['tipo']     = 'permit_empty|in_list[' . implode(',', array_keys(self::TIPOS)) . ']';
        // Situação e órgão vêm do catálogo de listas configuráveis.
        $this->validationRules['situacao'] = 'permit_empty|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('situacao_processo')) . ']';
        $this->validationRules['orgao']    = 'permit_empty|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('orgao')) . ']';
    }

    /**
     * Órgãos reutilizando o catálogo "orgao" (não duplicar cadastro).
     */
    public static function orgaosComChaves(): array
    {
        return ConfiguracaoListaModel::opcoesCategoria('orgao');
    }

    /**
     * Builder com mulher, atendimento, medida protetiva e responsável.
     */
    public function comDados()
    {
        return $this->select(
            'processos.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'dados_mulher.data_nascimento AS mulher_nascimento, '
            . 'dados_mulher.bairro AS mulher_bairro, '
            . 'dados_mulher.municipio AS mulher_municipio, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . 'medidas_protetivas.numero AS medida_numero, '
            . 'medidas_protetivas.situacao AS medida_situacao, '
            . 'users.nome AS responsavel_nome'
        )
            ->join('dados_mulher', 'dados_mulher.id = processos.mulher_id')
            ->join('atendimentos', 'atendimentos.id = processos.atendimento_id', 'left')
            ->join('medidas_protetivas', 'medidas_protetivas.id = processos.medida_protetiva_id', 'left')
            ->join('users', 'users.id = processos.responsavel_id', 'left');
    }
}
