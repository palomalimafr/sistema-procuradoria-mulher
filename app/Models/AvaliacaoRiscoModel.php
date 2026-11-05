<?php

namespace App\Models;

use CodeIgniter\Model;

class AvaliacaoRiscoModel extends Model
{
    protected $table            = 'avaliacoes_risco';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'acolhimento_id', 'data_hora', 'profissional_id',
        'ameaca_morte', 'agressao_fisica', 'estrangulamento',
        'agressor_arma', 'ameaca_com_arma', 'violencia_aumentando',
        'tentou_separar', 'controlador', 'perseguicao',
        'criancas_envolvidas', 'dependencia_financeira', 'local_seguro',
        'descumpriu_medida',
        'observacoes_profissionais', 'classificacao', 'classificacao_sugerida',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'acolhimento_id' => 'required|integer',
        'data_hora'      => 'required',
        'profissional_id' => 'permit_empty|integer',
        'classificacao'  => 'required|in_list[baixo,medio,alto,extremo]',
        'classificacao_sugerida' => 'permit_empty|in_list[baixo,medio,alto,extremo]',
    ];
    protected $validationMessages = [
        'classificacao' => [
            'required' => 'Informe a classificação de risco do profissional.',
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
     * Perguntas do formulário (fonte única em app/Database/PerguntasRisco.php).
     */
    public function perguntas(): array
    {
        return require APPPATH . 'Database/PerguntasRisco.php';
    }

    /**
     * Sugestão automática calculada a partir das respostas (apenas auxílio).
     * Itens críticos sinalizam risco extremo; não substitui a avaliação
     * do profissional, que é sempre a classificação registrada.
     *
     * @param array<string, mixed> $respostas
     */
    public function sugestaoClassificacao(array $respostas): string
    {
        $criticos  = ['ameaca_morte', 'estrangulamento', 'agressor_arma', 'ameaca_com_arma', 'descumpriu_medida'];
        $moderados = ['agressao_fisica', 'violencia_aumentando', 'tentou_separar', 'controlador', 'perseguicao'];

        $sim = static fn (string $chave): bool => ! empty($respostas[$chave]);

        foreach ($criticos as $chave) {
            if ($sim($chave)) {
                return 'extremo';
            }
        }

        $n = 0;
        foreach ($moderados as $chave) {
            if ($sim($chave)) {
                $n++;
            }
        }

        if ($n >= 3) {
            return 'alto';
        }
        if ($n >= 2) {
            return 'medio';
        }
        if ($n === 1 && ! $sim('local_seguro')) {
            return 'medio';
        }

        return 'baixo';
    }

    /**
     * Acolhimentos com avaliação registrada + atendimento + mulher + profissional.
     */
    public function comDados()
    {
        return $this->select(
            'avaliacoes_risco.*, '
            . 'acolhimentos.status AS acolhimento_status, '
            . 'acolhimentos.data_hora AS acolhimento_data_hora, '
            . 'atendimentos.id AS atendimento_id, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'users.nome AS profissional_nome'
        )
            ->join('acolhimentos', 'acolhimentos.id = avaliacoes_risco.acolhimento_id')
            ->join('atendimentos', 'atendimentos.id = acolhimentos.atendimento_id')
            ->join('dados_mulher', 'dados_mulher.id = atendimentos.mulher_id')
            ->join('users', 'users.id = avaliacoes_risco.profissional_id', 'left');
    }

    /**
     * Última avaliação de risco de um acolhimento (ou null).
     */
    public function porAcolhimento(int $acolhimentoId): ?array
    {
        return $this->where('acolhimento_id', $acolhimentoId)
            ->orderBy('id', 'DESC')
            ->first();
    }
}