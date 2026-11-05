<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

class AtendimentoModel extends Model
{
    protected $table            = 'atendimentos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'protocolo', 'data_hora', 'mulher_id', 'caso_id', 'profissional_id',
        'unidade_setor', 'forma_atendimento', 'origem', 'origem_outra',
        'motivo_atendimento', 'classificacao', 'prioridade', 'situacao',
        'descricao_caso', 'observacoes', 'pedido_sos_id',
        'data_encerramento', 'motivo_encerramento', 'motivo_encerramento_outra',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public const FORM_ATENDIMENTO = [
        'presencial' => 'Presencial',
        'telefone'   => 'Telefone',
        'whatsapp'   => 'WhatsApp',
        'email'      => 'E-mail',
        'online'     => 'Online',
        'visita'     => 'Visita',
        'outro'      => 'Outro',
    ];

    /**
     * Origens configuráveis da especificação.
     * O valor armazenado é a chave; a lista poderá virar configuração futuramente.
     */
    public const ORIGENS = [
        'demanda_espontanea' => 'Demanda espontânea',
        'camara_municipal'   => 'Câmara Municipal',
        'prefeitura'         => 'Prefeitura',
        'cras'               => 'CRAS',
        'creas'              => 'CREAS',
        'delegacia'          => 'Delegacia',
        'policia_militar'    => 'Polícia Militar',
        'ministerio_publico' => 'Ministério Público',
        'defensoria_publica' => 'Defensoria Pública',
        'poder_judiciario'   => 'Poder Judiciário',
        'unidade_saude'      => 'Unidade de Saúde',
        'escola'             => 'Escola',
        'conselho_tutelar'   => 'Conselho Tutelar',
        'familiar'           => 'Familiar',
        'denuncia'           => 'Denúncia',
        'outros'             => 'Outros',
    ];

    public const PRIORIDADES = [
        'emergencia' => 'Emergência',
        'alta'       => 'Alta',
        'media'      => 'Média',
        'baixa'      => 'Baixa',
    ];

    /**
     * Situação do atendimento (não confundir com o ciclo completo do "caso",
     * que será implementado em etapa futura).
     */
    public const SITUACOES = [
        'novo'                    => 'Novo',
        'aguardando_atendimento'  => 'Aguardando atendimento',
        'em_andamento'            => 'Em andamento',
        'encerrado'               => 'Encerrado',
    ];

    public const MOTIVOS_ENCERRAMENTO = [
        'atendimento_concluido'      => 'Atendimento concluído',
        'encaminhamento_concluido'   => 'Encaminhamento concluído',
        'caso_resolvido'             => 'Caso resolvido',
        'solicitacao_assistida'      => 'Solicitação da assistida',
        'perda_contato'              => 'Perda de contato',
        'mudanca_municipio'          => 'Mudança de município',
        'encaminhamento_outro_orgao' => 'Encaminhamento para outro órgão',
        'outros'                     => 'Outros',
    ];

    /**
     * Limiar (em horas) a partir do qual um atendimento que aguarda é
     * considerado atrasado, por prioridade. Base configurável para o filtro
     * "casos atrasados" da fila.
     */
    public const LIMIAR_ATRASO_HORAS = [
        'emergencia' => 1,
        'alta'       => 4,
        'media'      => 24,
        'baixa'      => 72,
    ];

    protected $validationRules = [
        'protocolo'        => 'required|max_length[30]',
        'data_hora'        => 'required',
        'mulher_id'        => 'required|integer',
        'profissional_id'  => 'permit_empty|integer',
        'forma_atendimento' => 'required|in_list[presencial,telefone,whatsapp,email,online,visita,outro]',
        'origem'           => 'required|in_list[demanda_espontanea,camara_municipal,prefeitura,cras,creas,delegacia,policia_militar,ministerio_publico,defensoria_publica,poder_judiciario,unidade_saude,escola,conselho_tutelar,familiar,denuncia,outros]',
        'prioridade'       => 'required|in_list[emergencia,alta,media,baixa]',
        'situacao'         => 'required|in_list[novo,aguardando_atendimento,em_andamento,encerrado]',
        'motivo_encerramento' => 'permit_empty|in_list[atendimento_concluido,encaminhamento_concluido,caso_resolvido,solicitacao_assistida,perda_contato,mudanca_municipio,encaminhamento_outro_orgao,outros]',
    ];
    protected $validationMessages = [
        'mulher_id' => [
            'required' => 'Selecione a mulher atendida.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);

        // Listas configuráveis via catálogo (com fallback para as constantes).
        $this->validationRules['forma_atendimento'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('tipo_atendimento')) . ']';
        $this->validationRules['origem'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('origem_atendimento')) . ']';
        $this->validationRules['prioridade'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('prioridade')) . ']';
        $this->validationRules['situacao'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('status_atendimento')) . ']';
        $this->validationRules['motivo_encerramento'] = 'permit_empty|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('motivo_encerramento')) . ']';
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
     * Próximo protocolo único no formato AT-AANN-NNNNNN (ex.: AT-26-000001).
     */
    public function proximoProtocolo(?int $ano = null): string
    {
        $ano = $ano ?? (int) date('y');
        $prefixo = 'AT-' . $ano . '-';

        $ultimo = $this->select('protocolo')
            ->like('protocolo', $prefixo, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        $sequencia = 0;
        if ($ultimo !== null && preg_match('/^AT-\\d{2}-(\\d+)$/', (string) $ultimo['protocolo'], $m) === 1) {
            $sequencia = (int) $m[1];
        }

        return sprintf('AT-%s-%06d', $ano, $sequencia + 1);
    }

    /**
     * Builder com join dos dados da mulher e do profissional responsável.
     */
    public function comDados()
    {
        return $this->select(
            'atendimentos.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'dados_mulher.data_nascimento AS mulher_nascimento, '
            . 'dados_mulher.bairro AS mulher_bairro, '
            . 'dados_mulher.municipio AS mulher_municipio, '
            . 'users.nome AS profissional_nome'
        )
            ->join('dados_mulher', 'dados_mulher.id = atendimentos.mulher_id')
            ->join('users', 'users.id = atendimentos.profissional_id', 'left');
    }

    /**
     * Expressão SQL usada para ordenar a fila pela urgência da prioridade.
     * Usa o prefixo real das tabelas (ex.: db_atendimentos nos testes).
     */
    public function ordemPrioridadeSql(): string
    {
        $tabela = $this->db->prefixTable('atendimentos');

        return "CASE {$tabela}.prioridade WHEN 'emergencia' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END";
    }

    /**
     * Atendimento mais recente de uma mulher que ainda não foi encerrado
     * (novo, aguardando atendimento ou em andamento). Usado para evitar
     * duplicidade ao direcionar um pedido SOS.
     */
    public function emAbertoPorMulher(int $mulherId): ?array
    {
        return $this->where('mulher_id', $mulherId)
            ->whereIn('situacao', ['novo', 'aguardando_atendimento', 'em_andamento'])
            ->orderBy('data_hora', 'DESC')
            ->first();
    }

    /**
     * Atendimento em aberto de uma mulher, porém SOMENTE quando existe
     * exatamente um. Usado para pré-selecionar o vínculo de forma segura:
     * havendo zero ou mais de um atendimento em aberto, nada é
     * pré-selecionado e a escolha fica com a profissional.
     */
    public function unicoAbertoPorMulher(int $mulherId): ?array
    {
        if ($mulherId <= 0) {
            return null;
        }

        $abertos = $this->comDados()
            ->where('atendimentos.mulher_id', $mulherId)
            ->whereIn('atendimentos.situacao', ['novo', 'aguardando_atendimento', 'em_andamento'])
            ->orderBy('atendimentos.data_hora', 'DESC')
            ->findAll(2);

        return count($abertos) === 1 ? $abertos[0] : null;
    }

    /**
     * Linha compacta de um atendimento, compartilhada pelos formulários
     * que precisam selecionar ou exibir o vínculo (busca AJAX, chips de
     * contexto). Aceita array oriundo de comDados() de atendimentos ou de
     * qualquer módulo que já traga o atendimento prefixado.
     */
    public static function linhaResumo(array $a): array
    {
        return [
            'id'               => (int) ($a['atendimento_id'] ?? $a['id'] ?? 0),
            'protocolo'        => (string) ($a['protocolo'] ?? $a['atendimento_protocolo'] ?? ''),
            'data_hora'        => (string) ($a['data_hora'] ?? $a['atendimento_data_hora'] ?? ''),
            'prioridade'       => (string) ($a['prioridade'] ?? $a['atendimento_prioridade'] ?? ''),
            'situacao'         => (string) ($a['situacao'] ?? $a['atendimento_situacao'] ?? ''),
            'mulher_id'        => (int) ($a['mulher_id'] ?? 0),
            'mulher_nome'      => (string) ($a['mulher_nome'] ?? ''),
            'mulher_cpf'       => (string) ($a['mulher_cpf'] ?? ''),
            'mulher_bairro'    => (string) ($a['mulher_bairro'] ?? ''),
            'mulher_municipio' => (string) ($a['mulher_municipio'] ?? ''),
            'data_hora_formatted' => ! empty($a['data_hora'] ?? $a['atendimento_data_hora'] ?? '')
                ? date('d/m/Y H:i', strtotime((string) ($a['data_hora'] ?? $a['atendimento_data_hora'])))
                : '',
        ];
    }

    /**
     * Atendimento originado de um pedido SOS específico, se existir.
     */
    public function porPedidoSos(int $pedidoSosId): ?array
    {
        return $this->where('pedido_sos_id', $pedidoSosId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Atendimentos de uma mulher (com dado do profissional responsável),
     * do mais recente para o mais antigo. Usado no Histórico da mulher.
     */
    public function listarPorMulher(int $mulherId): array
    {
        return $this->comDados()
            ->where('atendimentos.mulher_id', $mulherId)
            ->orderBy('atendimentos.data_hora', 'DESC')
            ->findAll();
    }

    /**
     * Mapa [mulher_id => ['situacao', 'data_hora']] com o ÚLTIMO atendimento
     * de cada mulher, usado na coluna Status da tabela de Pesquisa.
     *
     * @param int[] $mulherIds
     *
     * @return array<int, array{situacao: string, data_hora: ?string}>
     */
    public function ultimosStatusPorMulheres(array $mulherIds): array
    {
        if ($mulherIds === []) {
            return [];
        }

        $linhas = $this->select('mulher_id, situacao, data_hora')
            ->whereIn('mulher_id', $mulherIds)
            ->orderBy('id', 'ASC')
            ->findAll();

        $ultimos = [];
        foreach ($linhas as $linha) {
            $ultimos[(int) $linha['mulher_id']] = [
                'situacao' => (string) $linha['situacao'],
                'data_hora' => $linha['data_hora'],
            ];
        }

        return $ultimos;
    }
}
