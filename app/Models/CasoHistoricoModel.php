<?php

namespace App\Models;

use CodeIgniter\Model;

class CasoHistoricoModel extends Model
{
    protected $table = 'caso_historico';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['caso_id', 'usuario_id', 'tipo_evento', 'descricao', 'dados_adicionais', 'created_at'];

    public function comUsuario()
    {
        return $this->select('caso_historico.*, users.nome AS usuario_nome')
            ->join('users', 'users.id = caso_historico.usuario_id', 'left');
    }
}
