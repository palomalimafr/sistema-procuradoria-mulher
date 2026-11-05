<?php

namespace App\Models;

use CodeIgniter\Model;

class MedidaDescumprimentoModel extends Model
{
    protected $table            = 'medida_descumprimentos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'medida_id', 'data_ocorrencia', 'descricao', 'observacoes',
        'historico', 'usuario_id',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'medida_id'       => 'required|integer',
        'data_ocorrencia' => 'required|valid_date[Y-m-d]',
        'descricao'       => 'required',
        'observacoes'     => 'permit_empty',
        'historico'       => 'permit_empty',
        'usuario_id'      => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        'data_ocorrencia' => [
            'required' => 'Informe a data da ocorrência.',
        ],
        'descricao' => [
            'required' => 'Descreva a ocorrência de descumprimento.',
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

    /**
     * Ocorrências de uma medida, do mais recente para o mais antigo.
     * Ocorrências anteriores nunca são apagadas ao registrar uma nova.
     */
    public function listarPorMedida(int $medidaId): array
    {
        return $this->select('medida_descumprimentos.*, users.nome AS usuario_nome')
            ->join('users', 'users.id = medida_descumprimentos.usuario_id', 'left')
            ->where('medida_id', $medidaId)
            ->orderBy('data_ocorrencia', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}