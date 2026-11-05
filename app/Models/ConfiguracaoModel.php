<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modelo dos pares chave -> valor das configurações do sistema
 * (SISTEMA > Configurações).
 *
 * Chaves registradas e seus padrões de exibição (usados somente enquanto
 * a configuração ainda não foi salva):
 *
 * - nome_sistema      -> "Sistema de Gestão"
 * - nome_instituicao  -> "Procuradoria da Mulher"
 * - nome_procuradoria -> "Procuradoria da Mulher"
 * - telefone, email, endereco, municipio, estado, logo -> null
 * - email_*           -> configuração do envio de e-mail (ver DEFAULTS)
 */
class ConfiguracaoModel extends Model
{
    protected $table            = 'sistema_configuracoes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['chave', 'valor'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'chave' => 'required|max_length[100]',
        'valor' => 'permit_empty|max_length[300]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /** @var array<string, string|null> Padrões de exibição por chave. */
    public const DEFAULTS = [
        'nome_sistema'      => 'Sistema de Gestão',
        'nome_instituicao'  => 'Procuradoria da Mulher',
        'nome_procuradoria' => 'Procuradoria da Mulher',
        'telefone'          => null,
        'email'             => null,
        'endereco'          => null,
        'municipio'         => null,
        'estado'            => null,
        'logo'              => null,

        // Envio de e-mail (convite de primeiro acesso). Sem host SMTP o
        // serviço usa o transporte nativo "mail" do PHP.
        'email_smtp_host'              => null,
        'email_smtp_port'              => '587',
        'email_smtp_user'              => null,
        'email_smtp_pass'              => null,
        'email_smtp_crypto'            => 'tls',
        'email_remetente_email'        => null,
        'email_remetente_nome'         => null,
        'email_convite_validade_horas' => '72',
    ];

    /**
     * Chaves cujo valor é segredo. O formulário de configurações nunca os
     * sobrescreve quando chegam vazios, evitando limpar a senha do SMTP
     * por accidento a cada salvamento da tela.
     *
     * @var string[]
     */
    public const CHAVES_SECRETAS = ['email_smtp_pass'];

    /**
     * Retorna o valor de uma chave de configuração.
     */
    public function obter(string $chave, ?string $padrao = null): ?string
    {
        $registro = $this->where('chave', $chave)->first();

        return $registro !== null && $registro['valor'] !== null && trim((string) $registro['valor']) !== ''
            ? (string) $registro['valor']
            : $padrao;
    }

    /**
     * Retorna o valor persistido de uma chave, sem aplicar o tratamento de
     * "vazio = ausente" de obter().
     *
     * Necessário onde a diferença importa: em "Criptografia", a escolha
     * "Nenhuma" é gravada como string vazia de propósito, e tratá-la como
     * ausente transformaria a opção em TLS sem o operador pedir.
     *
     * @return string|null valor bruto, ou null quando a chave nunca foi salva
     */
    public function obterBruto(string $chave): ?string
    {
        $registro = $this->where('chave', $chave)->first();

        return $registro === null ? null : (string) ($registro['valor'] ?? '');
    }

    /**
     * Retorna todas as configurações como mapa chave -> valor, mesclando os
     * padrões de exibição com o que está persistido.
     *
     * @return array<string, string|null>
     */
    public function obterTodos(): array
    {
        $mapa = self::DEFAULTS;

        $linhas = $this->findAll();
        foreach ($linhas as $linha) {
            $mapa[(string) $linha['chave']] = $linha['valor'] !== null && trim((string) $linha['valor']) !== ''
                ? (string) $linha['valor']
                : null;
        }

        return $mapa;
    }

    /**
     * Persiste um mapa chave -> valor (upsert por chave). Valores vazios são
     * gravados como null; a linha da chave é criada na primeira gravação.
     *
     * @param array<string, string|null> $campos
     */
    public function salvarCampos(array $campos): void
    {
        foreach ($campos as $chave => $valor) {
            $valor = $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

            $existente = $this->where('chave', $chave)->first();
            if ($existente !== null) {
                $this->update((int) $existente['id'], ['chave' => $chave, 'valor' => $valor]);
            } else {
                $this->insert(['chave' => $chave, 'valor' => $valor]);
            }
        }
    }
}