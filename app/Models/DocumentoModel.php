<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;

/**
 * Metadados dos documentos armazenados fora do docroot.
 *
 * A exclusão é LÓGICA: ao "excluir", o registro recebe excluido_em/excluido_por
 * e desaparece das consultas padrão, preservando o arquivo físico e o
 * histórico. O model usa soft delete (deletedField = excluido_em).
 */
class DocumentoModel extends Model
{
    protected $table            = 'documentos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mulher_id', 'atendimento_id', 'processo_id', 'medida_protetiva_id', 'caso_id',
        'encaminhamento_id', 'manifestacao_id', 'tipo', 'sigilo',
        'arquivo_original', 'arquivo', 'tamanho_bytes', 'tipo_mime',
        'observacoes', 'usuario_id', 'excluido_em', 'excluido_por',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'excluido_em';

    /**
     * Tipos de documento (padrão do repositório da Procuradoria).
     * Estrutura já preparada para tipos configuráveis no futuro.
     */
    public const TIPOS = [
        'documentos_pessoais'  => 'Documentos pessoais',
        'boletim_ocorrencia'   => 'Boletim de ocorrência',
        'medida_protetiva'     => 'Medida protetiva',
        'relatorios'           => 'Relatórios',
        'encaminhamentos'      => 'Encaminhamentos',
        'declaracoes'          => 'Declarações',
        'oficios'              => 'Ofícios',
        'requerimentos'        => 'Requerimentos',
        'comprovantes'         => 'Comprovantes',
        'documentos_judiciais' => 'Documentos judiciais',
        'outros'               => 'Outros',
    ];

    /**
     * Classificação de sigilo do documento.
     */
    public const SIGILOS = [
        'publico_admin' => 'Público administrativo',
        'restrito'      => 'Restrito',
        'confidencial'  => 'Confidencial',
        'sigiloso'      => 'Sigiloso',
    ];

    /**
     * Cores (Tailwind) dos selos de sigilo na interface.
     */
    public const SIGILO_APARENCIA = [
        'publico_admin' => 'bg-gray-50 border-gray-200 text-gray-600',
        'restrito'      => 'bg-blue-50 border-blue-200 text-blue-700',
        'confidencial'  => 'bg-amber-50 border-amber-200 text-amber-700',
        'sigiloso'      => 'bg-red-50 border-red-200 text-red-700',
    ];

    /**
     * Tamanho máximo de upload em bytes (10 MB).
     */
    public const MAX_TAMANHO_BYTES = 10485760;

    /**
     * Extensões permitidas (allowlist — executáveis e formatos perigosos são
     * rejeitados na validação).
     */
    public const EXTENSOES_PERMITIDAS = 'pdf,png,jpg,jpeg,gif,webp,doc,docx,odt,txt,csv,xls,xlsx,pptx,rtf';

    protected $validationRules = [
        'mulher_id' => 'required|integer',
        'tipo'      => 'required|in_list[documentos_pessoais,boletim_ocorrencia,medida_protetiva,relatorios,encaminhamentos,declaracoes,oficios,requerimentos,comprovantes,documentos_judiciais,outros]',
        'sigilo'    => 'required|in_list[publico_admin,restrito,confidencial,sigiloso]',
        'arquivo'   => 'required|max_length[255]',
        'arquivo_original' => 'required|max_length[255]',
        'tipo_mime' => 'permit_empty|max_length[120]',
        'usuario_id' => 'permit_empty|integer',
    ];
    protected $validationMessages = [
        'mulher_id' => [
            'required' => 'Localize e selecione a mulher vinculada.',
        ],
        'tipo' => [
            'required' => 'Informe o tipo do documento.',
        ],
        'sigilo' => [
            'required' => 'Informe a classificação de sigilo.',
        ],
        'arquivo' => [
            'required'   => 'O arquivo armazenado é obrigatório.',
            'max_length' => 'Nome do arquivo armazenado muito longo.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function __construct(?ConnectionInterface $connection = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($connection, $validation);

        // Tipo do documento via catálogo; sigilo permanece lista fixa.
        $this->validationRules['tipo'] = 'required|in_list[' . implode(',', ConfiguracaoListaModel::chavesCategoria('tipo_documento')) . ']';
        $this->validationRules['sigilo'] = 'required|in_list[' . implode(',', array_keys(self::SIGILOS)) . ']';
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
     * Builder com mulher e, quando houver, atendimento, processo, medida,
     * encaminhamento, manifestação e usuário responsável.
     */
    public function comDados()
    {
        return $this->select(
            'documentos.*, '
            . 'dados_mulher.nome_completo AS mulher_nome, '
            . 'dados_mulher.cpf AS mulher_cpf, '
            . 'atendimentos.protocolo AS atendimento_protocolo, '
            . 'processos.numero_processo AS processo_numero, '
            . 'medidas_protetivas.numero AS medida_numero, '
            . 'encaminhamentos.status AS encaminhamento_status, '
            . 'medidas_protetivas.situacao AS medida_situacao, '
            . 'manifestacoes.protocolo AS manifestacao_protocolo, '
            . 'manifestacoes.situacao AS manifestacao_situacao, '
            . 'users.nome AS usuario_nome'
        )
            ->join('dados_mulher', 'dados_mulher.id = documentos.mulher_id')
            ->join('atendimentos', 'atendimentos.id = documentos.atendimento_id', 'left')
            ->join('processos', 'processos.id = documentos.processo_id', 'left')
            ->join('medidas_protetivas', 'medidas_protetivas.id = documentos.medida_protetiva_id', 'left')
            ->join('encaminhamentos', 'encaminhamentos.id = documentos.encaminhamento_id', 'left')
            ->join('manifestacoes', 'manifestacoes.id = documentos.manifestacao_id', 'left')
            ->join('users', 'users.id = documentos.usuario_id', 'left');
    }
}
