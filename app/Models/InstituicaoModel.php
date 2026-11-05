<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

/**
 * Instituições/órgãos da Rede de Proteção. Sem exclusão física: instituição
 * desativada preserva o histórico e deixa de aparecer para novos
 * encaminhamentos.
 */
class InstituicaoModel extends Model
{
    protected $table            = 'rede_instituicoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nome', 'tipo', 'endereco', 'municipio', 'telefone', 'whatsapp',
        'email', 'responsavel', 'horario_atendimento', 'observacoes', 'ativo',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Tipos de instituição da rede (estrutura simples, pronta para configuração).
     */
    public const TIPOS = [
        'assistencia_social' => 'Assistência Social',
        'saude'              => 'Saúde',
        'seguranca_publica'  => 'Segurança Pública',
        'justica'            => 'Justiça',
        'abrigamento'        => 'Abrigamento',
        'educacao'           => 'Educação',
        'insercao_renda'     => 'Inserção produtiva / renda',
        'outros'             => 'Outros',
    ];

    protected $validationRules = [
        'nome'              => 'required|max_length[120]',
        'tipo'              => 'required|in_list[assistencia_social,saude,seguranca_publica,justica,abrigamento,educacao,insercao_renda,outros]',
        'endereco'          => 'permit_empty|max_length[200]',
        'municipio'         => 'permit_empty|max_length[100]',
        'telefone'          => 'permit_empty|max_length[20]',
        'whatsapp'          => 'permit_empty|max_length[20]',
        'email'             => 'permit_empty|valid_email|max_length[120]',
        'responsavel'       => 'permit_empty|max_length[120]',
        'horario_atendimento' => 'permit_empty|max_length[120]',
        'observacoes'       => 'permit_empty',
        'ativo'             => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages = [
        'nome' => [
            'required' => 'Informe o nome da instituição.',
        ],
        'tipo' => [
            'required' => 'Informe o tipo da instituição.',
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
     * Nomes dos serviços oferecidos pela instituição.
     *
     * @return array<int, string>
     */
    public function servicos(int $instituicaoId): array
    {
        return array_column(
            (new ServicoInstituicaoModel())
                ->where('instituicao_id', $instituicaoId)
                ->orderBy('nome', 'ASC')
                ->findAll(),
            'nome'
        );
    }

    /**
     * Substitui os serviços da instituição (lista dinâmica).
     *
     * @param array<int, string> $servicos
     */
    public function substituirServicos(int $instituicaoId, array $servicos): void
    {
        $servicoModel = new ServicoInstituicaoModel();
        $servicoModel->where('instituicao_id', $instituicaoId)->delete();

        foreach (array_values(array_filter(array_map('trim', $servicos))) as $nome) {
            $servicoModel->insert([
                'instituicao_id' => $instituicaoId,
                'nome'           => $nome,
            ]);
        }
    }
}