<?php

namespace App\Models;

use CodeIgniter\Model;

class PedidoSosModel extends Model
{
    protected $table            = 'pedidos_sos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['mulher_id', 'data_hora', 'status', 'latitude', 'longitude', 'visualizado_em'];

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

    /**
     * Situação do pedido: recém-criado aguardando triagem ou já encaminhado
     * para um atendimento pela equipe.
     */
    public const STATUS = [
        'pendente'    => 'Pendente',
        'direcionado' => 'Direcionado',
    ];

    // Validation
    protected $validationRules = [
        'mulher_id' => 'required|integer',
        'status'    => 'required|in_list[pendente,direcionado]',
        'latitude'  => 'required|numeric|greater_than_equal_to[-90]|less_than_equal_to[90]',
        'longitude' => 'required|numeric|greater_than_equal_to[-180]|less_than_equal_to[180]',
    ];
    protected $validationMessages   = [
        'mulher_id' => ['required' => 'Erro interno: mulher não identificada.'],
        'latitude'  => ['required' => 'A localização é obrigatória para enviar o pedido SOS.'],
        'longitude' => ['required' => 'A localização é obrigatória para enviar o pedido SOS.'],
    ];
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

    public function listarComMulher(int $perPage = 10)
    {
        return $this->select('pedidos_sos.*, dados_mulher.nome_completo AS nome_mulher')
            ->join('dados_mulher', 'dados_mulher.id = pedidos_sos.mulher_id')
            ->orderBy('pedidos_sos.data_hora', 'DESC')
            ->paginate($perPage);
    }

    public function comMulher(int $id): ?array
    {
        return $this->select('pedidos_sos.*, dados_mulher.nome_completo AS nome_mulher')
            ->join('dados_mulher', 'dados_mulher.id = pedidos_sos.mulher_id')
            ->where('pedidos_sos.id', $id)
            ->first();
    }

    public function totalPendentes(): int
    {
        return $this->where('status', 'pendente')->countAllResults();
    }

    /**
     * Pedidos SOS pendentes ainda não visualizados pelo administrador,
     * do mais recente para o mais antigo.
     */
    public function novosParaAdmin(): array
    {
        return $this->select('pedidos_sos.id, pedidos_sos.data_hora, pedidos_sos.latitude, pedidos_sos.longitude, dados_mulher.nome_completo AS mulher')
            ->join('dados_mulher', 'dados_mulher.id = pedidos_sos.mulher_id')
            ->where('pedidos_sos.status', 'pendente')
            ->where('pedidos_sos.visualizado_em', null)
            ->orderBy('pedidos_sos.data_hora', 'DESC')
            ->findAll();
    }

    public function marcarVisualizado(int $id): bool
    {
        return $this->update($id, ['visualizado_em' => date('Y-m-d H:i:s')]);
    }

    /**
     * Marca o pedido como já direcionado para um atendimento, mantendo o
     * registro e o histórico preservados. Não sobrescreve pedidos que não
     * estejam pendentes.
     */
    public function marcarDirecionado(int $id): bool
    {
        $pedido = $this->find($id);

        if ($pedido === null || $pedido['status'] !== 'pendente') {
            return false;
        }

        return $this->update($id, ['status' => 'direcionado']);
    }

    /**
     * Pedidos SOS de uma mulher, do mais recente para o mais antigo.
     * Somente leitura — a lógica de criação do SOS não é alterada.
     */
    public function listarPorMulher(int $mulherId): array
    {
        return $this->where('mulher_id', $mulherId)
            ->orderBy('data_hora', 'DESC')
            ->findAll();
    }
}