<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

/**
 * Serviços oferecidos por cada instituição da Rede de Proteção (lista livre
 * e dinâmica, sem catálogo fechado).
 */
class ServicoInstituicaoModel extends Model
{
    protected $table            = 'rede_servicos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'instituicao_id', 'nome',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'instituicao_id' => 'required|integer',
        'nome'           => 'required|max_length[120]',
    ];
    protected $validationMessages = [
        'instituicao_id' => [
            'required' => 'Informe a instituição do serviço.',
        ],
        'nome' => [
            'required' => 'Informe o nome do serviço.',
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
    }
}