<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\DocumentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\InstituicaoModel;
use App\Models\ManifestacaoHistoricoModel;
use App\Models\ManifestacaoModel;
use App\Models\MulherModel;
use App\Models\UserModel;

/**
 * Módulo OUVIDORIA > Denúncias/Ouvidoria.
 *
 * Recebe, registra, consulta e acompanha manifestações relacionadas à
 * Procuradoria da Mulher. A identificação da pessoa é opcional (manifestações
 * anônimas são permitidas); o vínculo com uma mulher já cadastrada também é
 * opcional. O encaminhamento para a Rede de Proteção reutiliza a estrutura
 * existente (rede_instituicoes). Anexos reutilizam o módulo Documentos.
 */
class Manifestacoes extends BaseController
{
    protected $manifestacaoModel;

    public function __construct()
    {
        $this->manifestacaoModel = new ManifestacaoModel();
    }

    /**
     * Garante acesso apenas à equipe da Procuradoria (admin/funcionário).
     */
    private function somenteEquipe()
    {
        if (! in_array((string) session()->get('perfil'), ['admin', 'funcionario'], true)) {
            return redirect()->to('/dashboard')->with('erro', 'Acesso restrito à equipe da Procuradoria.');
        }

        return null;
    }

    private function equipeAtiva(): array
    {
        return (new UserModel())
            ->whereIn('perfil', ['admin', 'funcionario'])
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->findAll();
    }

    /**
     * Listagem de manifestações com filtros compactos (padrão de Tarefas).
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'protocolo'   => trim((string) $this->request->getGet('protocolo')),
            'tipo'        => (string) $this->request->getGet('tipo'),
            'origem'      => (string) $this->request->getGet('origem'),
            'prioridade'  => (string) $this->request->getGet('prioridade'),
            'situacao'    => (string) $this->request->getGet('situacao'),
            'responsavel' => max(0, (int) $this->request->getGet('responsavel')),
            'data_de'     => trim((string) $this->request->getGet('data_de')),
            'data_ate'    => trim((string) $this->request->getGet('data_ate')),
        ];

        $model = $this->manifestacaoModel->comDados();

        if ($filtros['protocolo'] !== '') {
            $model->like('manifestacoes.protocolo', $filtros['protocolo']);
        }
        if ($filtros['tipo'] !== '' && array_key_exists($filtros['tipo'], ManifestacaoModel::TIPOS)) {
            $model->where('manifestacoes.tipo', $filtros['tipo']);
        }
        if ($filtros['origem'] !== '' && array_key_exists($filtros['origem'], ConfiguracaoListaModel::todasCategoria('origem_manifestacao'))) {
            $model->where('manifestacoes.origem', $filtros['origem']);
        }
        if ($filtros['prioridade'] !== '' && array_key_exists($filtros['prioridade'], $this->manifestacaoModel->prioridades())) {
            $model->where('manifestacoes.prioridade', $filtros['prioridade']);
        }
        if ($filtros['situacao'] !== '' && array_key_exists($filtros['situacao'], ConfiguracaoListaModel::todasCategoria('situacao_manifestacao'))) {
            $model->where('manifestacoes.situacao', $filtros['situacao']);
        }
        if ($filtros['responsavel'] > 0) {
            $model->where('manifestacoes.responsavel_id', $filtros['responsavel']);
        }
        if ($this->dataValida($filtros['data_de'])) {
            $model->where('manifestacoes.data_hora >=', $filtros['data_de'] . ' 00:00:00');
        }
        if ($this->dataValida($filtros['data_ate'])) {
            $model->where('manifestacoes.data_hora <=', $filtros['data_ate'] . ' 23:59:59');
        }

        $manifestacoes = $model->orderBy($this->manifestacaoModel->ordemPrioridadeSql(), 'ASC')
            ->orderBy('manifestacoes.data_hora', 'DESC')
            ->orderBy('manifestacoes.id', 'DESC')
            ->paginate(15);

        return view('manifestacoes/lista', [
            'titulo'         => 'Denúncias/Ouvidoria',
            'manifestacoes'  => $manifestacoes,
            'pager'          => $this->manifestacaoModel->pager,
            'filtros'        => $filtros,
            'tipos'          => ManifestacaoModel::TIPOS,
            'origens'        => ConfiguracaoListaModel::todasCategoria('origem_manifestacao'),
            'prioridades'    => $this->manifestacaoModel->prioridades(),
            'situacoes'      => ConfiguracaoListaModel::todasCategoria('situacao_manifestacao'),
            'equipe'         => $this->equipeAtiva(),
        ]);
    }

    /**
     * Formulário de nova manifestação, com mulher opcionalmente pré-selecionada.
     */
    public function nova()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherContexto        = (int) $this->request->getGet('mulher_id');
        $mulherPreselecionada  = $this->mulherPreselecionada($mulherContexto);

        return view('manifestacoes/form', [
            'titulo'                => 'Nova Manifestação',
            'editando'              => false,
            'manifestacao'          => null,
            'mulherPreselecionada'  => $mulherPreselecionada,
            'contexto'              => (new ContextoMulher())->montar($mulherContexto),
            'usuarioId'             => (int) session()->get('usuario_id'),
            'usuarioNome'           => (string) session()->get('usuario_nome'),
            'equipe'                => $this->equipeAtiva(),
            'tipos'                 => ManifestacaoModel::TIPOS,
            'origens'               => ConfiguracaoListaModel::opcoesCategoria('origem_manifestacao'),
            'prioridades'           => $this->manifestacaoModel->prioridades(),
        ]);
    }

    /**
     * Registra a manifestação: protocolo único, data/hora e situação inicial
     * "nova" são gerados automaticamente.
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $dados = $this->dadosDoPost();

        if (! empty($dados['mulher_id']) && ! (new MulherModel())->find((int) $dados['mulher_id'])) {
            return redirect()->back()->withInput()->with('erros', ['mulher_id' => 'A mulher selecionada não existe mais.']);
        }

        $dados['protocolo'] = $this->manifestacaoModel->proximoProtocolo();
        $dados['data_hora'] = date('Y-m-d H:i:s');
        $dados['situacao'] = 'nova';
        $dados['instituicao_id'] = null;

        if (! $this->manifestacaoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->manifestacaoModel->errors());
        }

        $id = (int) $this->manifestacaoModel->getInsertID();

        (new ManifestacaoHistoricoModel())->registrar(
            $id,
            'criacao',
            null,
            'nova',
            (int) session()->get('usuario_id'),
            'Manifestação registrada.'
        );

        $this->auditar('CREATE', 'manifestacoes', $id, 'manifestacao', (string) ($dados['protocolo'] ?? ''), [
            'protocolo' => (string) ($dados['protocolo'] ?? ''),
        ]);

        return redirect()->to('/manifestacoes/' . $id)
            ->with('sucesso', 'Manifestação registrada. Protocolo ' . $dados['protocolo'] . '.');
    }

    /**
     * Ficha da manifestação: dados completos, situação com fluxo, documentos
     * vinculados e histórico.
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $manifestacao = $this->manifestacaoModel->comDados()
            ->where('manifestacoes.id', $id)
            ->first();

        if ($manifestacao === null) {
            return redirect()->to('/manifestacoes')->with('erro', 'Manifestação não encontrada.');
        }

        $documentos = (new DocumentoModel())->comDados()
            ->where('documentos.manifestacao_id', $id)
            ->orderBy('documentos.id', 'DESC')
            ->findAll();

        return view('manifestacoes/detalhes', [
            'titulo'             => 'Manifestação #' . $id,
            'manifestacao'       => $manifestacao,
            'tipos'              => ManifestacaoModel::TIPOS,
            'origens'            => ConfiguracaoListaModel::todasCategoria('origem_manifestacao'),
            'prioridades'        => $this->manifestacaoModel->prioridades(),
            'situacoes'          => ConfiguracaoListaModel::todasCategoria('situacao_manifestacao'),
            'fluxoStatus'        => ManifestacaoModel::FLUXO_STATUS,
            'historico'          => (new ManifestacaoHistoricoModel())->listarPorManifestacao($id),
            'documentos'         => $documentos,
            'instituicoesAtivas' => (new InstituicaoModel())->where('ativo', 1)->orderBy('nome', 'ASC')->findAll(),
        ]);
    }

    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $manifestacao = $this->manifestacaoModel->comDados()
            ->where('manifestacoes.id', $id)
            ->first();

        if ($manifestacao === null) {
            return redirect()->to('/manifestacoes')->with('erro', 'Manifestação não encontrada.');
        }

        return view('manifestacoes/form', [
            'titulo'                => 'Editar Manifestação #' . $id,
            'editando'              => true,
            'manifestacao'          => $manifestacao,
            'mulherPreselecionada'  => $this->mulherPreselecionada((int) $manifestacao['mulher_id']),
            'usuarioId'             => (int) session()->get('usuario_id'),
            'usuarioNome'           => (string) session()->get('usuario_nome'),
            'equipe'                => $this->equipeAtiva(),
            'tipos'                 => ManifestacaoModel::TIPOS,
            'origens'               => ConfiguracaoListaModel::opcoesCategoria('origem_manifestacao'),
            'prioridades'           => $this->manifestacaoModel->prioridades(),
        ]);
    }

    /**
     * Atualiza o MESMO registro (não cria nova manifestação). A situação é
     * acompanhada na ficha; o destino (instituição) é preservado.
     */
    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->manifestacaoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/manifestacoes')->with('erro', 'Manifestação não encontrada.');
        }

        $dados = $this->dadosDoPost();

        if (! empty($dados['mulher_id']) && ! (new MulherModel())->find((int) $dados['mulher_id'])) {
            return redirect()->back()->withInput()->with('erros', ['mulher_id' => 'A mulher selecionada não existe mais.']);
        }

        if (! $this->manifestacaoModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('erros', $this->manifestacaoModel->errors());
        }

        $historico = new ManifestacaoHistoricoModel();
        $usuarioId = (int) session()->get('usuario_id');

        $responsavelAnterior = $atual['responsavel_id'] === null ? null : (int) $atual['responsavel_id'];
        if ($responsavelAnterior !== $dados['responsavel_id']) {
            $historico->registrar(
                $id,
                'responsavel',
                null,
                null,
                $usuarioId,
                'Responsável alterado no registro da manifestação.'
            );
        }

        $historico->registrar($id, 'edicao', null, null, $usuarioId, 'Dados da manifestação editados.');

        $this->auditar('UPDATE', 'manifestacoes', $id, 'manifestacao', (string) ($atual['protocolo'] ?? ''), []);

        return redirect()->to('/manifestacoes/' . $id)
            ->with('sucesso', 'Manifestação atualizada.');
    }

    /**
     * Atualização de situação conforme o fluxo (mesmo registro, nunca apaga).
     * Encaminhar para a Rede exige uma instituição ativa; resolver/encerrar
     * grava data_encerramento.
     */
    public function mudarSituacao(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->manifestacaoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/manifestacoes')->with('erro', 'Manifestação não encontrada.');
        }

        $novoStatus = (string) $this->request->getPost('status');
        if (! array_key_exists($novoStatus, ConfiguracaoListaModel::todasCategoria('situacao_manifestacao'))) {
            return redirect()->back()->with('erro', 'Situação inválida para a manifestação.');
        }

        $permitidos = ManifestacaoModel::FLUXO_STATUS[$atual['situacao']] ?? [];
        if (! in_array($novoStatus, $permitidos, true)) {
            return redirect()->back()->with('erro', 'Transição de situação não permitida para esta manifestação.');
        }

        $instituicaoId = null;
        $instituicaoNome = null;

        if ($novoStatus === 'encaminhada') {
            $instituicaoId = (int) $this->request->getPost('instituicao_id');

            if ($instituicaoId <= 0) {
                return redirect()->back()->with('erro', 'Selecione a instituição da Rede de Proteção para o encaminhamento.');
            }

            $instituicao = (new InstituicaoModel())->where('ativo', 1)->find($instituicaoId);
            if ($instituicao === null) {
                return redirect()->back()->with('erro', 'A instituição selecionada não existe ou está inativa.');
            }
            $instituicaoNome = (string) $instituicao['nome'];
        }

        $dadosUpdate = [
            'situacao'         => $novoStatus,
            'instituicao_id'   => $novoStatus === 'encaminhada' ? $instituicaoId : $atual['instituicao_id'],
            'data_encerramento' => in_array($novoStatus, ManifestacaoModel::SITUACOES_FINALIZADAS, true)
                ? date('Y-m-d H:i:s')
                : null,
        ];

        $this->manifestacaoModel->update($id, $dadosUpdate);

        $historico = new ManifestacaoHistoricoModel();
        $usuarioId = (int) session()->get('usuario_id');

        if ($novoStatus === 'encaminhada') {
            $historico->registrar(
                $id,
                'encaminhamento',
                (string) $atual['situacao'],
                'encaminhada',
                $usuarioId,
                'Encaminhada para ' . $instituicaoNome . '.'
            );
        } else {
            $historico->registrar(
                $id,
                'situacao',
                (string) $atual['situacao'],
                $novoStatus,
                $usuarioId,
                in_array($novoStatus, ManifestacaoModel::SITUACOES_FINALIZADAS, true)
                    ? 'Manifestação ' . ConfiguracaoListaModel::rotuloCategoria('situacao_manifestacao', $novoStatus) . '.'
                    : null
            );
        }

        if ($novoStatus === 'encaminhada') {
            $acaoAuditoria = 'FORWARD';
        } elseif (in_array($novoStatus, ManifestacaoModel::SITUACOES_FINALIZADAS, true)) {
            $acaoAuditoria = 'CLOSE';
        } else {
            $acaoAuditoria = 'STATUS_CHANGE';
        }

        $this->auditar($acaoAuditoria, 'manifestacoes', $id, 'manifestacao', (string) ($atual['protocolo'] ?? ''), [
            'situacao_de'   => (string) ($atual['situacao'] ?? ''),
            'situacao_para' => $novoStatus,
            'instituicao'   => $instituicaoNome ?? null,
        ]);

        return redirect()->to('/manifestacoes/' . $id)
            ->with('sucesso', 'Situação da manifestação atualizada.');
    }

    /**
     * Localiza manifestações existentes (AJAX) para o vínculo opcional de
     * documentos (anexos da manifestação).
     */
    public function buscarManifestacoes()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo = trim((string) $this->request->getGet('term'));
        $model = $this->manifestacaoModel->comDados();

        if ($termo !== '') {
            $model->groupStart()
                ->like('manifestacoes.protocolo', $termo)
                ->orLike('manifestacoes.pessoa_nome', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('manifestacoes.descricao', $termo)
                ->groupEnd();
        }

        $manifestacoes = $model->orderBy('manifestacoes.data_hora', 'DESC')
            ->limit(15)
            ->findAll();

        $resultado = array_map(
            fn (array $m): array => $this->linhaManifestacao($m),
            $manifestacoes
        );

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['manifestacoes' => $resultado]);
    }

    private function linhaManifestacao(array $m): array
    {
        $id = (int) ($m['manifestacao_id'] ?? $m['id'] ?? 0);
        $protocolo = (string) ($m['manifestacao_protocolo'] ?? $m['protocolo'] ?? '');
        $tipo = (string) ($m['tipo'] ?? 'outro');
        $situacao = (string) ($m['situacao'] ?? 'nova');
        $anonimo = (bool) ($m['anonimo'] ?? false);

        return [
            'id'             => $id,
            'protocolo'      => $protocolo,
            'titulo'         => '#' . $id . ($protocolo !== '' ? ' · ' . $protocolo : ''),
            'tipo'           => $tipo,
            'tipo_rotulo'    => ManifestacaoModel::TIPOS[$tipo] ?? $tipo,
            'situacao'       => $situacao,
            'situacao_rotulo' => ConfiguracaoListaModel::rotuloCategoria('situacao_manifestacao', $situacao),
            'anonimo'        => $anonimo,
            'pessoa_nome'    => ! $anonimo ? (string) ($m['pessoa_nome'] ?? '') : '',
            'mulher_id'      => (int) ($m['mulher_id'] ?? 0),
            'mulher_nome'    => (string) ($m['mulher_nome'] ?? ''),
            'resumo'         => mb_strimwidth((string) ($m['descricao'] ?? ''), 0, 90, '…'),
        ];
    }

    /**
     * Monta os campos editáveis a partir do POST.
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $anonimo = (int) ($this->request->getPost('anonimo') ?? 0) === 1 ? 1 : 0;

        return [
            'tipo'             => $this->chaveValida('tipo', ManifestacaoModel::TIPOS, 'denuncia'),
            'origem'           => $this->chaveValida('origem', ConfiguracaoListaModel::todasCategoria('origem_manifestacao'), 'presencial'),
            'anonimo'          => $anonimo,
            'pessoa_nome'      => $anonimo === 1 ? null : $limpar($this->request->getPost('pessoa_nome')),
            'pessoa_cpf'       => $anonimo === 1 ? null : $limpar($this->request->getPost('pessoa_cpf')),
            'pessoa_telefone'  => $anonimo === 1 ? null : $limpar($this->request->getPost('pessoa_telefone')),
            'pessoa_email'     => $anonimo === 1 ? null : $limpar($this->request->getPost('pessoa_email')),
            'mulher_id'        => $this->idOpcional('mulher_id'),
            'descricao'        => $limpar($this->request->getPost('descricao')),
            'local_relacionado' => $limpar($this->request->getPost('local_relacionado')),
            'observacoes'      => $limpar($this->request->getPost('observacoes')),
            'prioridade'       => $this->chaveValida('prioridade', $this->manifestacaoModel->prioridades(), 'media'),
            'responsavel_id'   => max(0, (int) $this->request->getPost('responsavel_id')) > 0
                ? (int) $this->request->getPost('responsavel_id')
                : ((int) session()->get('usuario_id') > 0 ? (int) session()->get('usuario_id') : null),
        ];
    }

    private function chaveValida(string $campo, array $opcoes, string $padrao): string
    {
        $valor = (string) $this->request->getPost($campo);

        return array_key_exists($valor, $opcoes) ? $valor : $padrao;
    }

    private function idOpcional(string $campo): ?int
    {
        $valor = (int) $this->request->getPost($campo);

        return $valor > 0 ? $valor : null;
    }

    private function dataValida(string $data): bool
    {
        return $data !== '' && (bool) strtotime($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;
    }

    private function mulherPreselecionada(int $mulherId): ?array
    {
        if ($mulherId <= 0) {
            return null;
        }

        $m = (new MulherModel())->find($mulherId);
        if ($m === null) {
            return null;
        }

        return [
            'id'              => (int) $m['id'],
            'nome'            => (string) $m['nome_completo'],
            'cpf'             => $m['cpf'] ?? '',
            'data_nascimento' => $m['data_nascimento'] ?? '',
            'bairro'          => $m['bairro'] ?? '',
            'municipio'       => $m['municipio'] ?? '',
        ];
    }
}