<?php

namespace App\Models;

use CodeIgniter\Model;

class CasoModel extends Model
{
    protected $table = 'casos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['protocolo', 'mulher_id', 'responsavel_id', 'prioridade', 'situacao', 'data_abertura', 'data_encerramento', 'motivo_encerramento', 'motivo_encerramento_outra', 'resumo'];

    public const SITUACOES = [
        'novo' => 'Novo', 'em_acolhimento' => 'Em acolhimento', 'em_acompanhamento' => 'Em acompanhamento',
        'encaminhado' => 'Encaminhado', 'aguardando_retorno' => 'Aguardando retorno', 'em_atendimento_rede' => 'Em atendimento pela rede',
        'resolvido' => 'Resolvido', 'encerrado' => 'Encerrado', 'arquivado' => 'Arquivado',
    ];
    public const SITUACOES_ATIVAS = ['novo', 'em_acolhimento', 'em_acompanhamento', 'encaminhado', 'aguardando_retorno', 'em_atendimento_rede', 'resolvido'];

    protected $validationRules = [
        'protocolo' => 'required|max_length[30]', 'mulher_id' => 'required|integer', 'responsavel_id' => 'permit_empty|integer',
        'prioridade' => 'required|in_list[emergencia,alta,media,baixa]',
        'situacao' => 'required|in_list[novo,em_acolhimento,em_acompanhamento,encaminhado,aguardando_retorno,em_atendimento_rede,resolvido,encerrado,arquivado]',
        'data_abertura' => 'required', 'motivo_encerramento' => 'permit_empty|max_length[40]', 'resumo' => 'permit_empty',
    ];

    public function comDados()
    {
        return $this->select('casos.*, dados_mulher.nome_completo AS mulher_nome, dados_mulher.cpf AS mulher_cpf, dados_mulher.data_nascimento AS mulher_nascimento, dados_mulher.bairro AS mulher_bairro, dados_mulher.zona AS mulher_zona, users.nome AS responsavel_nome')
            ->join('dados_mulher', 'dados_mulher.id = casos.mulher_id')
            ->join('users', 'users.id = casos.responsavel_id', 'left');
    }

    public static function gerarProtocolo(): string
    {
        return 'CAS-' . date('ym') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
