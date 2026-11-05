<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\EncaminhamentoModel;
use App\Models\MulherModel;
use App\Models\TarefaModel;
use App\Models\UserModel;

class Tarefas extends BaseController
{
    /**
     * Visões = filtros sobre a MESMA tabela de tarefas.
     */
    private const VISOES = [
        'todas'        => 'Todas',
        'pendentes'    => 'Pendentes',
        'em_andamento' => 'Em andamento',
        'concluidas'   => 'Concluídas',
        'atrasadas'    => 'Atrasadas',
    ];

    protected $tarefaModel;

    public function __construct()
    {
        $this->tarefaModel = new TarefaModel();
    }

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
     * Listagem de tarefas com visões e filtros compactos (painel recolhível).
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $visao = (string) $this->request->getGet('visao');
        if (! array_key_exists($visao, self::VISOES)) {
            $visao = 'todas';
        }

        $filtros = [
            'q'               => trim((string) $this->request->getGet('q')),
            'responsavel_id'  => max(0, (int) $this->request->getGet('responsavel')),
            'status'          => (string) $this->request->getGet('status'),
            'prioridade'      => (string) $this->request->getGet('prioridade'),
            'data_de'         => trim((string) $this->request->getGet('data_de')),
            'data_ate'        => trim((string) $this->request->getGet('data_ate')),
            'atendimento'     => trim((string) $this->request->getGet('atendimento')),
            'encaminhamento'  => trim((string) $this->request->getGet('encaminhamento')),
        ];

        $model = $this->tarefaModel->comDados();

        if ($visao === 'pendentes') {
            $model->where('tarefas.status', 'pendente');
        } elseif ($visao === 'em_andamento') {
            $model->where('tarefas.status', 'em_andamento');
        } elseif ($visao === 'concluidas') {
            $model->where('tarefas.status', 'concluida');
        } elseif ($visao === 'atrasadas') {
            $model->whereIn('tarefas.status', TarefaModel::STATUS_ATIVOS)
                ->where('tarefas.prazo <', date('Y-m-d'))
                ->where('tarefas.prazo IS NOT NULL');
        }

        if ($filtros['q'] !== '') {
            $termo = $filtros['q'];
            $model->groupStart()
                ->like('tarefas.titulo', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }
        if ($filtros['responsavel_id'] > 0) {
            $model->where('tarefas.responsavel_id', $filtros['responsavel_id']);
        }
        if ($filtros['status'] !== '' && array_key_exists($filtros['status'], ConfiguracaoListaModel::todasCategoria('status_tarefa'))) {
            $model->where('tarefas.status', $filtros['status']);
        }
        if ($filtros['prioridade'] !== '' && array_key_exists($filtros['prioridade'], TarefaModel::prioridades())) {
            $model->where('tarefas.prioridade', $filtros['prioridade']);
        }
        if ($filtros['data_de'] !== '' && $this->dataValida($filtros['data_de'])) {
            $model->where('tarefas.prazo >=', $filtros['data_de']);
        }
        if ($filtros['data_ate'] !== '' && $this->dataValida($filtros['data_ate'])) {
            $model->where('tarefas.prazo <=', $filtros['data_ate']);
        }
        if ($filtros['atendimento'] !== '') {
            $model->like('atendimentos.protocolo', $filtros['atendimento']);
        }
        if ($filtros['encaminhamento'] !== '') {
            $model->groupStart()
                ->Like('encaminhamentos.orgao', $filtros['encaminhamento'])
                ->orWhere('encaminhamentos.id', (int) $filtros['encaminhamento'])
                ->groupEnd();
        }

        $tarefas = $model->orderBy('tarefas.prioridade', 'ASC')
            ->orderBy('tarefas.prazo', 'ASC')
            ->orderBy('tarefas.created_at', 'DESC')
            ->orderBy('tarefas.id', 'DESC')
            ->findAll();

        $data = [
            'titulo'     => 'Tarefas',
            'tarefas'    => $tarefas,
            'visao'      => $visao,
            'visoes'     => self::VISOES,
            'filtros'    => $filtros,
            'status'     => ConfiguracaoListaModel::todasCategoria('status_tarefa'),
            'prioridades' => TarefaModel::prioridades(),
            'equipe'     => $this->equipeAtiva(),
            'hoje'       => date('Y-m-d'),
        ];

        return view('tarefas/lista', $data);
    }

    /**
     * Formulário de nova tarefa, com vínculos opcionais pré-selecionados.
     */
    public function nova()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulher = null;
        $atendimento = null;
        $encaminhamento = null;
        $mulherContexto = 0;

        $encaminhamentoId = (int) $this->request->getGet('encaminhamento_id');
        if ($encaminhamentoId > 0) {
            $e = (new EncaminhamentoModel())->comDados()
                ->where('encaminhamentos.id', $encaminhamentoId)
                ->first();
            if ($e !== null) {
                $encaminhamento = $this->linhaEncaminhamento($e);
                $mulher = $this->mulherPreselecionada((int) $e['mulher_id']);
                $mulherContexto = (int) $e['mulher_id'];
                $atendimento = ! empty($e['atendimento_id'])
                    ? $this->atendimentoPreselecionado((int) $e['atendimento_id'])
                    : null;
            }
        }

        $atendimentoId = (int) $this->request->getGet('atendimento_id');
        if ($atendimentoId > 0 && $atendimento === null) {
            $atendimento = $this->atendimentoPreselecionado($atendimentoId);
            if ($atendimento !== null && $mulher === null) {
                $mulher = $this->mulherPreselecionada((int) $atendimento['mulher_id']);
                $mulherContexto = (int) $atendimento['mulher_id'];
            }
        }

        $mulherId = (int) $this->request->getGet('mulher_id');
        if ($mulherId > 0 && $mulher === null) {
            $mulher = $this->mulherPreselecionada($mulherId);
            $mulherContexto = $mulherId;
        }

        return view('tarefas/form', [
            'titulo'          => 'Nova Tarefa',
            'editando'        => false,
            'tarefa'          => null,
            'mulherPreselecionada' => $mulher,
            'atendimentoPreselecionado' => $atendimento,
            'encaminhamentoPreselecionado' => $encaminhamento,
            'contexto' => (new ContextoMulher())->montar($mulherContexto),
            'usuarioId'       => (int) session()->get('usuario_id'),
            'usuarioNome'     => (string) session()->get('usuario_nome'),
            'equipe'          => $this->equipeAtiva(),
            'status'          => ConfiguracaoListaModel::opcoesCategoria('status_tarefa'),
            'prioridades'     => TarefaModel::prioridades(),
            'hoje'            => date('Y-m-d'),
        ]);
    }

    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $dados = $this->dadosDoPost();
        if ($dados['status'] === '') {
            $dados['status'] = 'pendente';
        }

        $erro = $this->validarVinculos($dados);
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        if ($dados['status'] === 'concluida' && empty($dados['data_conclusao'])) {
            $dados['data_conclusao'] = date('Y-m-d H:i:s');
        }

        if (! $this->tarefaModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->tarefaModel->errors());
        }

        $id = (int) $this->tarefaModel->getInsertID();

        $this->auditar('CREATE', 'tarefas', $id, 'tarefa', null, [
            'titulo'     => (string) ($dados['titulo'] ?? ''),
            'prioridade' => (string) ($dados['prioridade'] ?? ''),
            'situacao'   => (string) ($dados['status'] ?? ''),
        ]);

        return redirect()->to('/tarefas/' . $id)
            ->with('sucesso', 'Tarefa registrada.');
    }

    /**
     * Ficha da tarefa com ações de status (mesmo registro, nunca apaga).
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $tarefa = $this->tarefaModel->comDados()
            ->where('tarefas.id', $id)
            ->first();

        if ($tarefa === null) {
            return redirect()->to('/tarefas')->with('erro', 'Tarefa não encontrada.');
        }

        return view('tarefas/detalhes', [
            'titulo'   => 'Tarefa #' . $id,
            'tarefa'   => $tarefa,
            'status'   => ConfiguracaoListaModel::todasCategoria('status_tarefa'),
            'fluxoStatus' => TarefaModel::FLUXO_STATUS,
            'prioridades' => TarefaModel::prioridades(),
            'atrasada' => TarefaModel::estaAtrasada($tarefa),
            'hoje'     => date('Y-m-d'),
        ]);
    }

    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $tarefa = $this->tarefaModel->comDados()
            ->where('tarefas.id', $id)
            ->first();

        if ($tarefa === null) {
            return redirect()->to('/tarefas')->with('erro', 'Tarefa não encontrada.');
        }

        return view('tarefas/form', [
            'titulo'          => 'Editar Tarefa #' . $id,
            'editando'        => true,
            'tarefa'          => $tarefa,
            'mulherPreselecionada' => $this->mulherPreselecionada((int) $tarefa['mulher_id']),
            'atendimentoPreselecionado' => ! empty($tarefa['atendimento_id'])
                ? $this->atendimentoPreselecionado((int) $tarefa['atendimento_id'])
                : null,
            'encaminhamentoPreselecionado' => ! empty($tarefa['encaminhamento_id'])
                ? $this->linhaEncaminhamento([
                    'id'                  => (int) $tarefa['encaminhamento_id'],
                    'mulher_nome'         => $tarefa['mulher_nome'] ?? '',
                    'encaminhamento_orgao' => $tarefa['encaminhamento_orgao'] ?? '',
                ])
                : null,
            'usuarioId'       => (int) session()->get('usuario_id'),
            'usuarioNome'     => (string) session()->get('usuario_nome'),
            'equipe'          => $this->equipeAtiva(),
            'status'          => ConfiguracaoListaModel::opcoesCategoria('status_tarefa'),
            'prioridades'     => TarefaModel::prioridades(),
            'hoje'            => date('Y-m-d'),
        ]);
    }

    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->tarefaModel->find($id);
        if ($atual === null) {
            return redirect()->to('/tarefas')->with('erro', 'Tarefa não encontrada.');
        }

        $dados = $this->dadosDoPost();

        $erro = $this->validarVinculos($dados);
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        if ($dados['status'] === 'concluida') {
            if (empty($dados['data_conclusao'])) {
                $dados['data_conclusao'] = date('Y-m-d H:i:s');
            }
        } else {
            $dados['data_conclusao'] = null;
        }

        if (! $this->tarefaModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('erros', $this->tarefaModel->errors());
        }

        $tarefaAudit = [
            'titulo' => (string) ($dados['titulo'] ?? ''),
        ];
        if ((string) ($atual['status'] ?? '') !== (string) ($dados['status'] ?? '')) {
            $tarefaAudit['situacao_de']   = (string) ($atual['status'] ?? '');
            $tarefaAudit['situacao_para'] = (string) ($dados['status'] ?? '');
        }
        if ((string) ($atual['prioridade'] ?? '') !== (string) ($dados['prioridade'] ?? '')) {
            $tarefaAudit['prioridade_de']   = (string) ($atual['prioridade'] ?? '');
            $tarefaAudit['prioridade_para'] = (string) ($dados['prioridade'] ?? '');
        }
        $this->auditar('UPDATE', 'tarefas', $id, 'tarefa', null, $tarefaAudit);

        return redirect()->to('/tarefas/' . $id)->with('sucesso', 'Tarefa atualizada.');
    }

    /**
     * Atualização simples de status conforme o fluxo (mesmo registro).
     * Concluir grava data_conclusao; nunca apaga a tarefa.
     */
    public function mudarStatus(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->tarefaModel->find($id);
        if ($atual === null) {
            return redirect()->to('/tarefas')->with('erro', 'Tarefa não encontrada.');
        }

        $novoStatus = (string) $this->request->getPost('status');
        if (! array_key_exists($novoStatus, ConfiguracaoListaModel::todasCategoria('status_tarefa'))) {
            return redirect()->back()->with('erro', 'Situação inválida para a tarefa.');
        }

        $permitidos = TarefaModel::FLUXO_STATUS[$atual['status']] ?? [];
        if (! in_array($novoStatus, $permitidos, true)) {
            return redirect()->back()->with('erro', 'Transição de status não permitida para esta tarefa.');
        }

        $dadosUpdate = ['status' => $novoStatus];
        if ($novoStatus === 'concluida') {
            $dadosUpdate['data_conclusao'] = date('Y-m-d H:i:s');
        }

        $this->tarefaModel->update($id, $dadosUpdate);

        $this->auditar('STATUS_CHANGE', 'tarefas', $id, 'tarefa', null, [
            'situacao_de'   => (string) ($atual['status'] ?? ''),
            'situacao_para' => $novoStatus,
        ]);

        return redirect()->to('/tarefas/' . $id)->with('sucesso', 'Status da tarefa atualizado.');
    }

    /**
     * Valida os vínculos opcionais (mulher/atendimento/encaminhamento).
     *
     * @return array|null
     */
    private function validarVinculos(array $dados): ?array
    {
        if (! empty($dados['mulher_id']) && ! (new MulherModel())->find((int) $dados['mulher_id'])) {
            return ['mulher_id' => 'A mulher selecionada não existe mais.'];
        }

        if (! empty($dados['atendimento_id']) && (int) $dados['atendimento_id'] > 0) {
            $atendimento = (new AtendimentoModel())->find((int) $dados['atendimento_id']);
            if ($atendimento === null) {
                return ['atendimento_id' => 'O atendimento selecionado não existe mais.'];
            }
            if (! empty($dados['mulher_id']) && (int) $atendimento['mulher_id'] !== (int) $dados['mulher_id']) {
                return ['atendimento_id' => 'O atendimento selecionado pertence a outra mulher.'];
            }
        }

        if (! empty($dados['encaminhamento_id']) && (int) $dados['encaminhamento_id'] > 0) {
            $e = (new EncaminhamentoModel())->find((int) $dados['encaminhamento_id']);
            if ($e === null) {
                return ['encaminhamento_id' => 'O encaminhamento selecionado não existe mais.'];
            }
            if (! empty($dados['mulher_id']) && (int) $e['mulher_id'] !== (int) $dados['mulher_id']) {
                return ['encaminhamento_id' => 'O encaminhamento selecionado pertence a outra mulher.'];
            }
        }

        return null;
    }

    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $status = (string) $this->request->getPost('status');
        if (! array_key_exists($status, ConfiguracaoListaModel::todasCategoria('status_tarefa'))) {
            $status = '';
        }

        return [
            'titulo'           => $limpar($this->request->getPost('titulo')),
            'descricao'        => $limpar($this->request->getPost('descricao')),
            'mulher_id'        => $limpar($this->request->getPost('mulher_id')) === null ? null : (int) $this->request->getPost('mulher_id'),
            'atendimento_id'   => $limpar($this->request->getPost('atendimento_id')) === null ? null : (int) $this->request->getPost('atendimento_id'),
            'encaminhamento_id' => $limpar($this->request->getPost('encaminhamento_id')) === null ? null : (int) $this->request->getPost('encaminhamento_id'),
            'responsavel_id'   => max(0, (int) $this->request->getPost('responsavel_id')) > 0 ? (int) $this->request->getPost('responsavel_id') : null,
            'prazo'            => $limpar($this->request->getPost('prazo')),
            'prioridade'       => $limpar($this->request->getPost('prioridade')),
            'status'           => $status,
            'data_conclusao'   => null,
        ];
    }

    private function dataValida(string $data): bool
    {
        return (bool) strtotime($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;
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

    private function atendimentoPreselecionado(int $atendimentoId): ?array
    {
        if ($atendimentoId <= 0) {
            return null;
        }

        $a = (new AtendimentoModel())->comDados()
            ->where('atendimentos.id', $atendimentoId)
            ->first();
        if ($a === null) {
            return null;
        }

        return [
            'id'               => (int) $a['id'],
            'protocolo'        => (string) $a['protocolo'],
            'data_hora'        => (string) $a['data_hora'],
            'mulher_id'        => (int) $a['mulher_id'],
            'mulher_nome'      => (string) $a['mulher_nome'],
            'data_hora_formatted' => ! empty($a['data_hora']) ? date('d/m/Y H:i', strtotime((string) $a['data_hora'])) : '',
        ];
    }

    private function linhaEncaminhamento(array $e): array
    {
        return [
            'id'          => (int) ($e['id'] ?? $e['encaminhamento_id'] ?? 0),
            'titulo'      => '#' . (int) ($e['id'] ?? $e['encaminhamento_id'] ?? 0)
                . ' · ' . ($e['mulher_nome'] ?? ''),
            'mulher_id'   => (int) ($e['mulher_id'] ?? 0),
            'mulher_nome' => (string) ($e['mulher_nome'] ?? ''),
            'orgao'       => (string) ($e['orgao'] ?? $e['encaminhamento_orgao'] ?? ''),
            'servico'     => (string) ($e['servico'] ?? $e['encaminhamento_servico'] ?? ''),
        ];
    }
}