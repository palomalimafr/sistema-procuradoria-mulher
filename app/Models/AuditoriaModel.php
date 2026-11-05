<?php

namespace App\Models;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Model;

/**
 * Trilha de auditoria do sistema (SISTEMA > Auditoria).
 *
 * Registro append-only de ações reais executadas nos módulos. O método
 * `registrar()` nunca interrompe a requisição e não expõe conteúdo sensível:
 * no JSON de `detalhes` entram apenas dados de contexto (protocolos, labels,
 * antes/depois de status), nunca senha, token, cookie ou corpo de documento.
 */
class AuditoriaModel extends Model
{
    protected $table            = 'sistema_auditoria';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'usuario_id', 'created_at', 'acao', 'modulo', 'registro_tipo',
        'registro_id', 'registro_ref', 'ip', 'rota', 'metodo', 'detalhes',
    ];

    protected bool $allowEmptyInserts = false;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = 'deleted_at';

    /** Padrão de ações da trilha (rótulos PT-BR para exibição na tela). */
    public const ACOES = [
        'LOGIN'           => 'Login',
        'LOGOUT'          => 'Logout',
        'CREATE'          => 'Cadastro',
        'UPDATE'          => 'Alteração',
        'DELETE'          => 'Exclusão',
        'VIEW_SENSITIVE'  => 'Visualização de dados sensíveis',
        'DOWNLOAD'        => 'Download',
        'UPLOAD'          => 'Upload',
        'STATUS_CHANGE'   => 'Mudança de status',
        'PRIORITY_CHANGE' => 'Mudança de prioridade',
        'FORWARD'         => 'Encaminhamento',
        'CLOSE'           => 'Encerramento',
        'CONVITE_ENVIADO' => 'Convite de primeiro acesso enviado',
        'CONVITE_REENVIADO' => 'Convite de primeiro acesso reenviado',
        'PRIMEIRO_ACESSO' => 'Primeiro acesso realizado',
    ];

    /** Rótulos dos módulos para a tela de auditoria. */
    public const MODULOS = [
        'auth'            => 'Autenticação',
        'usuarios'        => 'Usuários',
        'mulheres'        => 'Mulheres',
        'mulher_perfil'   => 'Perfil da mulher',
        'pedidos_sos'     => 'Pedidos SOS',
        'acolhimentos'    => 'Acolhimentos',
        'avaliacoes'      => 'Avaliação de risco',
        'atendimentos'    => 'Atendimentos',
        'encaminhamentos' => 'Encaminhamentos',
        'agenda'          => 'Agenda',
        'tarefas'         => 'Tarefas',
        'processos'       => 'Processos',
        'documentos'      => 'Documentos',
        'medidas'         => 'Medidas protetivas',
        'manifestacoes'   => 'Manifestações',
        'instituicoes'    => 'Rede de proteção',
        'configuracoes'   => 'Configurações',
        'listas'          => 'Listas configuráveis',
    ];

    /**
     * Grava uma entrada na trilha. Falhas nunca derrubam a aplicação:
     * retorna false (silencioso) se algo der errado, mantendo o fluxo real.
     */
    public function registrar(
        string $acao,
        string $modulo,
        ?int $usuarioId = null,
        ?string $registroTipo = null,
        ?int $registroId = null,
        ?string $registroRef = null,
        array $informacoes = [],
        ?RequestInterface $request = null
    ): bool {
        try {
            $request = $request ?? service('request');

            return $this->insert([
                'usuario_id'    => $usuarioId,
                'acao'          => $acao,
                'modulo'        => $modulo,
                'registro_tipo' => $registroTipo,
                'registro_id'   => $registroId,
                'registro_ref'  => $registroRef === null ? null : mb_substr($registroRef, 0, 120),
                'ip'            => $this->ipDaRequisicao($request),
                'rota'          => $this->stringSegura(uri_string(), 150),
                'metodo'        => $request === null ? null : mb_strtoupper((string) $request->getMethod()),
                'detalhes'      => $informacoes === [] ? null : json_encode($informacoes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Listagem da tela (mais recentes primeiro) com os filtros compactos.
     *
     * @return array Linhas já paginadas (25 por página).
     */
    public function filtrar(array $filtros): array
    {
        $builder = $this->select('sistema_auditoria.*, users.nome AS usuario_nome, users.email AS usuario_email')
            ->join('users', 'users.id = sistema_auditoria.usuario_id', 'left');

        if (! empty($filtros['usuario_id'])) {
            $builder->where('sistema_auditoria.usuario_id', (int) $filtros['usuario_id']);
        }

        if (! empty($filtros['acao']) && array_key_exists($filtros['acao'], self::ACOES)) {
            $builder->where('sistema_auditoria.acao', $filtros['acao']);
        }

        if (! empty($filtros['modulo']) && array_key_exists($filtros['modulo'], self::MODULOS)) {
            $builder->where('sistema_auditoria.modulo', $filtros['modulo']);
        }

        if (! empty($filtros['registro'])) {
            $termo = trim((string) $filtros['registro']);
            $builder->groupStart()
                ->like('sistema_auditoria.registro_ref', $termo)
                ->orWhere('sistema_auditoria.registro_id', max(0, (int) $termo))
                ->groupEnd();
        }

        if (! empty($filtros['ip'])) {
            $builder->like('sistema_auditoria.ip', trim((string) $filtros['ip']));
        }

        if (! empty($filtros['data_de'])) {
            $builder->where('sistema_auditoria.created_at >=', $filtros['data_de'] . ' 00:00:00');
        }

        if (! empty($filtros['data_ate'])) {
            $builder->where('sistema_auditoria.created_at <=', $filtros['data_ate'] . ' 23:59:59');
        }

        return $builder->orderBy('sistema_auditoria.id', 'DESC')->paginate(25);
    }

    /** Rótulo duma ação para exibição (fallback: a própria chave). */
    public static function rotuloAcao(string $acao): string
    {
        return self::ACOES[$acao] ?? $acao;
    }

    /** Rótulo dum módulo para exibição (fallback: a própria chave). */
    public static function rotuloModulo(string $modulo): string
    {
        return self::MODULOS[$modulo] ?? $modulo;
    }

    /** Decodifica o JSON de detalhes com segurança. */
    public static function detalhesDe(array $linha): array
    {
        $detalhes = $linha['detalhes'] ?? null;
        if ($detalhes === null || $detalhes === '') {
            return [];
        }

        $decodificado = json_decode((string) $detalhes, true);

        return is_array($decodificado) ? $decodificado : [];
    }

    private function ipDaRequisicao(?RequestInterface $request): ?string
    {
        if ($request === null) {
            return null;
        }

        try {
            $ip = (string) $request->getIPAddress();
        } catch (\Throwable $e) {
            return null;
        }

        return $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null;
    }

    private function stringSegura(?string $valor, int $maximo): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : mb_substr($valor, 0, $maximo);
    }
}