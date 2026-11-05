<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;

/**
 * Convites de primeiro acesso das mulheres cadastradas pela equipe.
 *
 * O token sorteado (32 bytes aleatórios) viaja apenas no link enviado por
 * e-mail; no banco fica somente o SHA-256 dele. Como o hash é de mão única, o
 * painel não consegue recuperar o link — e um vazamento do banco não permite
 * forjar um convite válido.
 */
class ConviteMulherModel extends Model
{
    protected $table            = 'mulher_convites';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mulher_id', 'token_hash', 'expira_em', 'enviado_em', 'utilizado_em',
        'invalidado_em',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts    = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'mulher_id'  => 'required|is_natural_no_zero',
        'token_hash' => 'required|max_length[64]',
        'expira_em'  => 'required|valid_date',
    ];
    protected $validationMessages = [
        'mulher_id'  => ['required' => 'O convite precisa estar vinculado a uma mulher.'],
        'token_hash' => ['required' => 'O convite precisa de um token válido.'],
    ];
    protected $skipValidation     = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];

    /** Convite utilizável (dentro do prazo e ainda não usado). */
    public const STATUS_VALIDO = 'valido';
    /** Convite cujo prazo de validade terminou. */
    public const STATUS_EXPIRADO = 'expirado';
    /** Convite que já criou a conta de acesso. */
    public const STATUS_UTILIZADO = 'utilizado';
    /** Convite cancelado pela equipe (reenvio ou troca de e-mail). */
    public const STATUS_INVALIDADO = 'invalidado';

    /** Validade padrão do convite, em horas. */
    public const VALIDADE_HORAS = 72;

    /**
     * Sorteia um token de 256 bits em hexadecimal. Não carrega nenhum dado da
     * mulher e não é adivinhável por força bruta.
     */
    public static function gerarToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** Deriva o hash que realmente é gravado no banco. */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Formato aceito vindo da URL. Validar antes de consultar o banco evita
     * transformações inúteis e descarta lixo de terceiros.
     */
    public static function tokenBemFormado(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * Cria um convite e devolve o token EM TEXTO PURO, que só pode ser usado
     * no envio do e-mail. O banco recebe apenas o hash.
     *
     * @return array{id: int, token: string, expira_em: string}
     */
    public function gerar(int $mulherId, ?int $validadeHoras = null): array
    {
        $validadeHoras = $validadeHoras !== null && $validadeHoras > 0
            ? $validadeHoras
            : self::VALIDADE_HORAS;

        $token     = self::gerarToken();
        $expiraEm  = (new DateTimeImmutable())->modify('+' . $validadeHoras . ' hours')->format('Y-m-d H:i:s');
        $agora     = date('Y-m-d H:i:s');

        $id = $this->insert([
            'mulher_id'  => $mulherId,
            'token_hash' => self::hashToken($token),
            'expira_em'  => $expiraEm,
            'enviado_em' => $agora,
        ]);

        return [
            'id'         => is_int($id) ? $id : (int) $id,
            'token'      => $token,
            'expira_em'  => $expiraEm,
        ];
    }

    /**
     * Localiza o convite pelo token recebido no link (busca pelo hash).
     */
    public function porToken(string $token): ?array
    {
        if (! self::tokenBemFormado($token)) {
            return null;
        }

        $convite = $this->where('token_hash', self::hashToken($token))->first();

        return $convite === null ? null : $convite;
    }

    /**
     * Classifica o convite para a tela de primeiro acesso. Um convite que já
     * foi utilizado tem prioridade sobre a expiração: para a mulher a
     * informação útil é "sua conta já foi criada".
     */
    public function statusDe(?array $convite): string
    {
        if ($convite === null) {
            return 'inexistente';
        }

        if (! empty($convite['utilizado_em'])) {
            return self::STATUS_UTILIZADO;
        }

        if (! empty($convite['invalidado_em'])) {
            return self::STATUS_INVALIDADO;
        }

        return strcmp((string) $convite['expira_em'], date('Y-m-d H:i:s')) <= 0
            ? self::STATUS_EXPIRADO
            : self::STATUS_VALIDO;
    }

    /** Último convite criado para a mulher, utilizado ou não. */
    public function ultimo(int $mulherId): ?array
    {
        $convite = $this->where('mulher_id', $mulherId)->orderBy('id', 'DESC')->first();

        return $convite === null ? null : $convite;
    }

    /**
     * Convite ainda utilizável a partir do hash guardado (uso interno e de
     * auditoria: o hash não permite abrir o link da mulher).
     */
    public function pendentePorHash(string $tokenHash): ?array
    {
        $convite = $this
            ->where('token_hash', $tokenHash)
            ->where('utilizado_em', null)
            ->where('invalidado_em', null)
            ->where('expira_em >', date('Y-m-d H:i:s'))
            ->first();

        return $convite === null ? null : $convite;
    }

    /** Convite ainda utilizável da mulher (não usado e dentro do prazo). */
    public function pendente(int $mulherId): ?array
    {
        $convite = $this
            ->where('mulher_id', $mulherId)
            ->where('utilizado_em', null)
            ->where('invalidado_em', null)
            ->where('expira_em >', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->first();

        return $convite === null ? null : $convite;
    }

    /**
     * Consome o convite. Só marca quando ainda não foi usado, para que duas
     * submissões simultâneas do mesmo link não criem duas contas.
     */
    public function marcarUtilizado(int $id): bool
    {
        return $this->update($id, ['utilizado_em' => date('Y-m-d H:i:s')]);
    }

    /**
     * Invalida os convites ainda abertos da mulher — usado quando um novo
     * convite é emitido ou quando o e-mail cadastrado muda, para que o link
     * antigo deixe de valer.
     *
     * Marca 'invalidado_em', e não 'utilizado_em': nenhum dos dois convites criou
     * conta, então dizer à mulher que "a conta já existe" seria mentira.
     */
    public function invalidarPendentes(int $mulherId): int
    {
        // Pelo builder, e não pelo model: no model a chamada cairia em
        // update($dados, null) e quebraria por "não há dados a atualizar".
        return $this->builder()
            ->where('mulher_id', $mulherId)
            ->where('utilizado_em', null)
            ->where('invalidado_em', null)
            ->update(['invalidado_em' => date('Y-m-d H:i:s')]);
    }
}
