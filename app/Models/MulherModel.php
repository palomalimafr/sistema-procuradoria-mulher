<?php

namespace App\Models;

use CodeIgniter\Model;
use DateInterval;
use DateTimeImmutable;

class MulherModel extends Model
{
    protected $table            = 'dados_mulher';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'nome_completo', 'nome_social', 'cpf', 'rg',
        'data_nascimento', 'sexo', 'estado_civil', 'escolaridade',
        'profissao', 'ocupacao', 'telefone', 'whatsapp', 'email',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro',
        'municipio', 'estado', 'ponto_referencia', 'zona',
        'possui_filhos', 'quantidade_filhos', 'quantidade_dependentes',
        'esta_gravida', 'possui_deficiencia',
        'e_idosa', 'situacao_vulnerabilidade', 'observacoes',
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
        'user_id'       => 'permit_empty|is_natural_no_zero',
        'nome_completo' => 'required|max_length[200]',
        'email'         => 'permit_empty|valid_email',
        'sexo'          => 'permit_empty|in_list[M,F,O]',
        'estado_civil'  => 'permit_empty|in_list[solteira,casada,divorciada,viuva,uniao_estavel,outra]',
        'zona'          => 'permit_empty|in_list[urbana,rural]',
    ];
    protected $validationMessages   = [
        'nome_completo' => ['required' => 'O nome completo é obrigatório.'],
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

    public function findByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * Localiza uma mulher pelo CPF ignorando máscara/pontuação.
     * Aceita registros gravados com e sem máscara (ex.: cadastro público).
     */
    public function buscarPorCpf(string $cpf): ?array
    {
        $digitos = preg_replace('/\D/', '', $cpf);

        if ($digitos === '') {
            return null;
        }

        $mulheres = $this->where('cpf !=', '')->findAll();

        foreach ($mulheres as $mulher) {
            if (preg_replace('/\D/', '', (string) $mulher['cpf']) === $digitos) {
                return $mulher;
            }
        }

        return null;
    }

    /**
     * Busca um usuário com perfil "mulher" pelo e-mail (para vincular o
     * cadastro administrativo a uma conta existente, sem criar nova conta).
     */
    public function findUserIdMulherPorEmail(?string $email): ?int
    {
        if (! $email || empty(trim($email))) {
            return null;
        }

        $user = $this->db->table('users')
            ->where('LOWER(email)', mb_strtolower(trim($email)))
            ->where('perfil', 'mulher')
            ->where('ativo', 1)
            ->get()
            ->getRowArray();

        return isset($user['id']) ? (int) $user['id'] : null;
    }

    /**
     * Aplica os filtros de pesquisa de mulheres (MULHERES > Pesquisa).
     * Retorna o próprio builder para encadear paginação/ordenação.
     *
     * @param array<string, string|null> $filtros
     */
    public function pesquisar(array $filtros)
    {
        $filtros = array_merge([
            'q'            => null,
            'nome'         => null,
            'cpf'          => null,
            'nome_social'  => null,
            'telefone'     => null,
            'whatsapp'     => null,
            'municipio'    => null,
            'bairro'       => null,
            'zona'         => null,
            'idade'        => null,
            'faixa_etaria' => null,
        ], $filtros);

        // Busca rápida por nome/cpf (equivale a pesquisar em vários campos).
        if (! empty($filtros['q'])) {
            $termo = trim((string) $filtros['q']);
            $this->groupStart()
                ->like('nome_completo', $termo)
                ->orLike('nome_social', $termo)
                ->orLike('cpf', $this->padraoDigitosLike($termo))
                ->groupEnd();
        }

        if (! empty($filtros['nome'])) {
            $this->like('nome_completo', (string) $filtros['nome']);
        }

        if (! empty($filtros['nome_social'])) {
            $this->like('nome_social', (string) $filtros['nome_social']);
        }

        // CPF, telefone e WhatsApp são comparados pelos dígitos, para casar
        // registros gravados com ou sem máscara (ex.: cadastro público).
        foreach (['cpf', 'telefone', 'whatsapp'] as $campo) {
            if (! empty($filtros[$campo])) {
                $digitos = $this->padraoDigitosLike((string) $filtros[$campo]);
                if ($digitos !== '') {
                    $this->like($campo, $digitos);
                }
            }
        }

        if (! empty($filtros['municipio'])) {
            $this->like('municipio', (string) $filtros['municipio']);
        }

        if (! empty($filtros['bairro'])) {
            $this->like('bairro', (string) $filtros['bairro']);
        }

        if (! empty($filtros['zona'])) {
            $this->where('zona', (string) $filtros['zona']);
        }

        $intervalo = $this->intervaloPorFaixaEtaria($filtros);
        if ($intervalo !== null) {
            $this->where('data_nascimento >=', $intervalo[0]);
            $this->where('data_nascimento <=', $intervalo[1]);
        }

        return $this;
    }

    /**
     * Transforma cada dígito do termo em um coringa "%" intercalado, permitindo
     * igualdade com valores gravados com ou sem máscara (000.000.000-00).
     */
    private function padraoDigitosLike(string $texto): string
    {
        $digitos = preg_replace('/\D/', '', $texto) ?? '';

        return $digitos === '' ? '' : implode('%', str_split($digitos));
    }

    /**
     * Converte os filtros de idade (exata) e faixa etária em intervalo de
     * data_nascimento, quando for o caso.
     *
     * @param array<string, string|null> $filtros
     *
     * @return string[]|null [de, ate] em Y-m-d (limites inclusivos)
     */
    private function intervaloPorFaixaEtaria(array $filtros): ?array
    {
        $hoje = new DateTimeImmutable('today');

        if ($filtros['idade'] !== '' && $filtros['idade'] !== null && is_numeric($filtros['idade'])) {
            $anos = (int) $filtros['idade'];
            if ($anos < 0) {
                return null;
            }

            return [
                $hoje->sub(new DateInterval('P' . ($anos + 1) . 'Y'))->add(new DateInterval('P1D'))->format('Y-m-d'),
                $hoje->sub(new DateInterval('P' . $anos . 'Y'))->format('Y-m-d'),
            ];
        }

        // Faixas etárias da especificação (base com dados reais).
        return match ($filtros['faixa_etaria']) {
            '0_17' => [
                $hoje->sub(new DateInterval('P18Y'))->add(new DateInterval('P1D'))->format('Y-m-d'),
                $hoje->format('Y-m-d'),
            ],
            '18_29' => [
                $hoje->sub(new DateInterval('P30Y'))->add(new DateInterval('P1D'))->format('Y-m-d'),
                $hoje->sub(new DateInterval('P18Y'))->format('Y-m-d'),
            ],
            '30_44' => [
                $hoje->sub(new DateInterval('P45Y'))->add(new DateInterval('P1D'))->format('Y-m-d'),
                $hoje->sub(new DateInterval('P30Y'))->format('Y-m-d'),
            ],
            '45_59' => [
                $hoje->sub(new DateInterval('P60Y'))->add(new DateInterval('P1D'))->format('Y-m-d'),
                $hoje->sub(new DateInterval('P45Y'))->format('Y-m-d'),
            ],
            '60_mais' => [
                $hoje->sub(new DateInterval('P60Y'))->format('Y-m-d'),
                $hoje->format('Y-m-d'),
            ],
            default => null,
        };
    }

    /**
     * Idade em anos a partir da data de nascimento (ou null se vazia).
     */
    public static function calcularIdade(?string $dataNascimento): ?int
    {
        if (! $dataNascimento || $dataNascimento === '') {
            return null;
        }

        $nascimento = DateTimeImmutable::createFromFormat('Y-m-d', $dataNascimento);

        return $nascimento === false ? null : $nascimento->diff(new DateTimeImmutable('today'))->y;
    }
}
