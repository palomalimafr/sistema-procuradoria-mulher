<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

/**
 * Catálogo de listas configuráveis do sistema (SISTEMA > Configurações).
 *
 * Cada item representa uma opção de uma categoria (formas de atendimento,
 * origens, prioridades, status/situações por módulo, motivos de encerramento,
 * órgãos e tipos de documento). A inativação é LÓGICA (`ativo = 0`): o item
 * deixa de aparecer em formulários/filtros novos, mas continua válido no
 * histórico e na consulta de rótulos dos registros antigos.
 *
 * Quando a categoria ainda não possui itens no banco (ex.: migração não
 * aplicada), os métodos caem para as CONSTANTES já existentes nos models,
 * mantendo o comportamento atual do sistema.
 */
class ConfiguracaoListaModel extends Model
{
    protected $table            = 'sistema_configuracoes_itens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['categoria', 'chave', 'rotulo', 'ativo', 'ordem'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Categorias disponíveis e seus agrupamentos para a tela administrativa.
     * A chave é o valor armazenado em `categoria` (usado pelas integrações).
     */
    public const CATEGORIAS = [
        'tipo_atendimento' => [
            'rotulo'   => 'Tipos de atendimento',
            'grupo'    => 'cadastros',
            'descricao' => 'Forma como o atendimento é prestado à mulher.',
        ],
        'origem_atendimento' => [
            'rotulo'   => 'Origens de atendimento',
            'grupo'    => 'cadastros',
            'descricao' => 'Como a mulher chegou ao atendimento.',
        ],
        'origem_manifestacao' => [
            'rotulo'   => 'Origens de manifestação',
            'grupo'    => 'cadastros',
            'descricao' => 'Canal pelo qual a manifestação foi recebida.',
        ],
        'prioridade' => [
            'rotulo'   => 'Prioridades',
            'grupo'    => 'fluxo',
            'descricao' => 'Nível de urgência compartilhado entre os módulos.',
        ],
        'motivo_encerramento' => [
            'rotulo'   => 'Motivos de encerramento',
            'grupo'    => 'fluxo',
            'descricao' => 'Razões para encerrar um atendimento.',
        ],
        'status_atendimento' => [
            'rotulo'   => 'Status de atendimento',
            'grupo'    => 'fluxo',
            'descricao' => 'Situações do atendimento na fila de trabalho.',
        ],
        'status_encaminhamento' => [
            'rotulo'   => 'Status de encaminhamento',
            'grupo'    => 'fluxo',
            'descricao' => 'Situações de um encaminhamento à rede.',
        ],
        'status_tarefa' => [
            'rotulo'   => 'Status de tarefa',
            'grupo'    => 'fluxo',
            'descricao' => 'Situações de uma tarefa da equipe.',
        ],
        'status_acolhimento' => [
            'rotulo'   => 'Status de acolhimento',
            'grupo'    => 'fluxo',
            'descricao' => 'Situações do acolhimento inicial.',
        ],
        'situacao_manifestacao' => [
            'rotulo'   => 'Situações de manifestação',
            'grupo'    => 'fluxo',
            'descricao' => 'Situações do acompanhamento da manifestação.',
        ],
        'situacao_processo' => [
            'rotulo'   => 'Situações de processo',
            'grupo'    => 'fluxo',
            'descricao' => 'Situações de um processo judicial.',
        ],
        'situacao_medida' => [
            'rotulo'   => 'Situações de medida protetiva',
            'grupo'    => 'fluxo',
            'descricao' => 'Situações de uma medida protetiva de urgência.',
        ],
        'orgao' => [
            'rotulo'   => 'Órgãos',
            'grupo'    => 'cadastros',
            'descricao' => 'Órgãos compartilhados por encaminhamentos, processos e medidas.',
        ],
        'tipo_documento' => [
            'rotulo'   => 'Tipos de documento',
            'grupo'    => 'documentos',
            'descricao' => 'Classificação dos documentos anexados aos registros.',
        ],
        'tipo_violencia' => [
            'rotulo'   => 'Tipos de violência',
            'grupo'    => 'cadastros',
            'descricao' => 'Tipos de violência a serem usados no futuro módulo de violência.',
        ],
    ];

    /** Grupos de exibição (ordem de apresentação). */
    public const GRUPOS = [
        'cadastros'  => 'Cadastros',
        'fluxo'      => 'Status e situações',
        'documentos' => 'Documentos',
    ];

    protected $validationRules = [
        'categoria' => 'required|max_length[40]',
        'chave'     => 'required|max_length[60]|regex_match[/^[a-z0-9_]+$/]',
        'rotulo'    => 'required|max_length[120]',
        'ativo'     => 'permit_empty|in_list[0,1]',
        'ordem'     => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        'chave'  => [
            'required'    => 'A chave é obrigatória.',
            'max_length'  => 'A chave é muito longa.',
            'regex_match' => 'A chave deve conter apenas letras minúsculas, números e sublinhado.',
        ],
        'rotulo' => [
            'required'   => 'O rótulo é obrigatório.',
            'max_length' => 'O rótulo é muito longo.',
        ],
        'categoria' => [
            'required' => 'A categoria é obrigatória.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);
    }

    /**
     * Valores padrão (constantes existentes) de uma categoria. Usados como
     * fallback enquanto a categoria não tiver itens no banco e como rótulos de
     * valores que nunca foram cadastrados no catálogo.
     */
    public static function padrao(string $categoria): array
    {
        $mapa = [
            'tipo_atendimento'       => AtendimentoModel::FORM_ATENDIMENTO,
            'origem_atendimento'     => AtendimentoModel::ORIGENS,
            'origem_manifestacao'    => ManifestacaoModel::ORIGENS,
            'prioridade'             => AtendimentoModel::PRIORIDADES,
            'motivo_encerramento'    => AtendimentoModel::MOTIVOS_ENCERRAMENTO,
            'status_atendimento'     => AtendimentoModel::SITUACOES,
            'status_encaminhamento'  => EncaminhamentoModel::STATUS,
            'status_tarefa'          => TarefaModel::STATUS,
            'status_acolhimento'     => AcolhimentoModel::STATUS,
            'situacao_manifestacao'  => ManifestacaoModel::SITUACOES,
            'situacao_processo'      => ProcessoModel::SITUACOES,
            'situacao_medida'        => MedidaProtetivaModel::SITUACOES,
            'orgao'                  => EncaminhamentoModel::ORGAOS,
            'tipo_documento'         => DocumentoModel::TIPOS,
            'tipo_violencia'         => [],
        ];

        return $mapa[$categoria] ?? [];
    }

    /**
     * Opções de uma categoria como mapa chave => rótulo, na ordem cadastrada.
     * Por padrão retorna apenas itens ativos (formulários e filtros novos).
     *
     * @return array<string, string>
     */
    public function opcoes(string $categoria, bool $apenasAtivos = true): array
    {
        $padrao = self::padrao($categoria);
        $linhas = $this->itens($categoria);

        if ($linhas === []) {
            // Sem itens cadastrados: mantém constante existente (migração pendente).
            return $padrao;
        }

        $resultado = [];
        foreach ($linhas as $linha) {
            if ($apenasAtivos && ! (int) $linha['ativo']) {
                continue;
            }
            $resultado[(string) $linha['chave']] = (string) $linha['rotulo'];
        }

        return $resultado;
    }

    /**
     * Todas as opções (ativas e inativas) de uma categoria, na ordem cadastrada.
     * Usado na validação in_list para não quebrar registros antigos e na
     * consulta de rótulos do histórico.
     *
     * @return array<string, string>
     */
    public function opcoesTodas(string $categoria): array
    {
        return $this->opcoes($categoria, false);
    }

    /**
     * Rótulo de exibição de uma chave (ativa ou inativa). Fallback para o
     * padrão dos models e, por fim, para a própria chave.
     */
    public function rotulo(string $categoria, string $chave): string
    {
        $todas = $this->opcoesTodas($categoria);

        return (string) ($todas[$chave] ?? self::padrao($categoria)[$chave] ?? $chave);
    }

    /**
     * Linhas (itens) de uma categoria, na ordem de exibição.
     *
     * @return array<int, array<string, mixed>>
     */
    public function itens(string $categoria): array
    {
        try {
            return $this->where('categoria', $categoria)
                ->orderBy('ordem', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();
        } catch (\Throwable $e) {
            // Tabela ainda não criada (migração pendente): fallback para constantes.
            return [];
        }
    }

    /**
     * Verifica se a categoria existe no catálogo.
     */
    public function categoriaValida(string $categoria): bool
    {
        return isset(self::CATEGORIAS[$categoria]);
    }

    /**
     * Verifica se já existe um item com a chave na categoria.
     */
    public function chaveCadastrada(string $categoria, string $chave): bool
    {
        return $this->where('categoria', $categoria)
            ->where('chave', $chave)
            ->countAllResults() > 0;
    }

    /**
     * Cadastra um novo item, já com a ordem no fim da categoria.
     *
     * @return int|false id do item criado ou false em caso de erro
     */
    public function criarItem(string $categoria, string $chave, string $rotulo)
    {
        if (! $this->categoriaValida($categoria)) {
            return false;
        }
        $chave = trim($chave);
        $rotulo = trim($rotulo);
        if ($chave === '' || $rotulo === '' || preg_match('/^[a-z0-9_]+$/', $chave) !== 1) {
            return false;
        }
        if ($this->chaveCadastrada($categoria, $chave)) {
            return false;
        }

        $quantidade = (int) $this->where('categoria', $categoria)->countAllResults();

        return $this->insert([
            'categoria' => $categoria,
            'chave'     => $chave,
            'rotulo'    => $rotulo,
            'ativo'     => 1,
            'ordem'     => $quantidade,
        ]);
    }

    /**
     * Acessos estáticos de conveniência (controllers, validação in_list e
     * rótulos em views): instanciam o catálogo por chamada.
     */
    public static function opcoesCategoria(string $categoria, bool $apenasAtivos = true): array
    {
        return (new self())->opcoes($categoria, $apenasAtivos);
    }

    public static function todasCategoria(string $categoria): array
    {
        return (new self())->opcoesTodas($categoria);
    }

    public static function rotuloCategoria(string $categoria, string $chave): string
    {
        return (new self())->rotulo($categoria, $chave);
    }

    /**
     * Todas as chaves (ativas e inativas) de uma categoria, na ordem cadastrada.
     * Usada no in_list da validação para não quebrar registros antigos.
     *
     * @return array<int, string>
     */
    public static function chavesCategoria(string $categoria): array
    {
        return array_keys((new self())->opcoesTodas($categoria));
    }

    /**
     * Renomeia o rótulo de um item (a chave nunca é alterada).
     */
    public function renomear(int $id, string $rotulo): bool
    {
        $item = $this->find($id);
        if ($item === null) {
            return false;
        }
        $rotulo = trim($rotulo);

        return $rotulo !== ''
            ? $this->update($id, ['rotulo' => $rotulo])
            : false;
    }

    /**
     * Alterna a situação ativo/inativo de um item. Sem exclusão física.
     */
    public function alternarAtivo(int $id): bool
    {
        $item = $this->find($id);
        if ($item === null) {
            return false;
        }

        return $this->update($id, ['ativo' => (int) $item['ativo'] ? 0 : 1]);
    }
}