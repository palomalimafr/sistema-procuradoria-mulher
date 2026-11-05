<?php

namespace App\Models;

use CodeIgniter\Model;

class CriancaModel extends Model
{
    protected $table            = 'criancas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nome', 'data_nascimento', 'sexo', 'rg', 'cpf', 'data_matricula',
        'nome_mae', 'nome_pai', 'telefone', 'endereco', 'cidade', 'uf',
        'escola', 'serie', 'observacoes', 'status',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'nome' => 'required|min_length[3]|max_length[150]',
        'sexo' => 'permit_empty|in_list[M,F]',
        'status' => 'required|in_list[ativo,inativo]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function responsaveis(int $criancaId)
    {
        return $this->db->table('responsaveis')
            ->where('crianca_id', $criancaId)
            ->get()
            ->getResultArray();
    }
}
