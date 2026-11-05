<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\EncaminhamentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\MulherModel;
use App\Models\AtendimentoModel;
use App\Models\InstituicaoModel;
use App\Models\UserModel;

class Encaminhamentos extends BaseController
{
    protected $encaminhamentoModel;

    public function __construct()
    {
        $this->encaminhamentoModel = new EncaminhamentoModel();
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
     * Formulário de novo encaminhamento. A mulher é sempre obrigatória; o
     * atendimento relacionado é opcional (quando aplicável).
     */
    public function novo()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherPreselecionada = $this->mulherPreselecionada((int) $this->request->getGet('mulher_id'));
        $atendimentoPreselecionado = null;

        $atendimentoId = (int) $this->request->getGet('atendimento_id');
        if ($atendimentoId > 0) {
            $atendimento = (new AtendimentoModel())->comDados()
                ->where('atendimentos.id', $atendimentoId)
                ->first();
            if ($atendimento !== null) {
                $atendimentoPreselecionado = $this->linhaAtendimento($atendimento);
                // Vincula também a mulher do atendimento.
                $mulherPreselecionada = $this->mulherPreselecionada((int) ($atendimento['mulher_id'] ?? 0));
            }
        }

        $data = [
            'titulo'                   => 'Novo Encaminhamento',
            'encaminhamento'           => null,
            'mulherPreselecionada'     => $mulherPreselecionada,
            'atendimentoPreselecionado' => $atendimentoPreselecionado,
            'contexto'                 => (new ContextoMulher())->montar((int) ($mulherPreselecionada['id'] ?? 0)),
            'usuarioNome'              => (string) session()->get('usuario_nome'),
            'orgaos'                   => ConfiguracaoListaModel::todasCategoria('orgao'),
            'servicos'                 => EncaminhamentoModel::SERVICOS,
            'prioridades'              => $this->encaminhamentoModel->prioridades(),
            'hoje'                     => date('Y-m-d\TH:i'),
        ];

        return view('encaminhamentos/form', $data);
    }

    /**
     * Registra o encaminhamento. O profissional responsável é SEMPRE o usuário
     * autenticado (não é permitido informar outra pessoa).
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherId = (int) $this->request->getPost('mulher_id');
        if ($mulherId <= 0) {
            return redirect()->back()->withInput()->with('erros', ['mulher_id' => 'Localize e selecione a mulher vinculada.']);
        }

        if (! (new MulherModel())->find($mulherId)) {
            return redirect()->back()->withInput()->with('erros', ['mulher_id' => 'A mulher selecionada não existe mais.']);
        }

        $atendimentoId = (int) $this->request->getPost('atendimento_id');
        if ($atendimentoId > 0) {
            $atendimento = (new AtendimentoModel())->find($atendimentoId);
            if ($atendimento === null) {
                return redirect()->back()->withInput()->with('erros', ['atendimento_id' => 'O atendimento selecionado não existe mais.']);
            }
            if ((int) $atendimento['mulher_id'] !== $mulherId) {
                return redirect()->back()->withInput()->with('erros', ['atendimento_id' => 'O atendimento selecionado pertence a outra mulher.']);
            }
        }

        $dados = $this->dadosDoPost();
        $dados['mulher_id'] = $mulherId;
        $dados['atendimento_id'] = $atendimentoId > 0 ? $atendimentoId : null;
        $dados['data_hora'] = ! empty($this->request->getPost('data_hora'))
            ? date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('data_hora')))
            : date('Y-m-d H:i:s');
        $dados['profissional_id'] = (int) session()->get('usuario_id') > 0 ? (int) session()->get('usuario_id') : null;

        $instituicaoId = (int) ($dados['instituicao_id'] ?? 0);
        if ($instituicaoId > 0) {
            $instituicao = (new InstituicaoModel())->find($instituicaoId);
            if ($instituicao === null || (int) $instituicao['ativo'] !== 1) {
                return redirect()->back()->withInput()->with('erros', [
                    'instituicao_id' => 'A instituição selecionada não está ativa na rede de proteção.',
                ]);
            }
            $dados['orgao'] = (string) $instituicao['nome'];
        } else {
            return redirect()->back()->withInput()->with('erros', [
                'instituicao_id' => 'Selecione a instituição da rede de proteção para o encaminhamento.',
            ]);
        }

        if (! $this->encaminhamentoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->encaminhamentoModel->errors());
        }

        $id = (int) $this->encaminhamentoModel->getInsertID();

        $this->auditar('FORWARD', 'encaminhamentos', $id, 'encaminhamento', (string) ($dados['orgao'] ?? ''), [
            'mulher_id'     => $mulherId,
            'instituicao'   => (string) ($dados['orgao'] ?? ''),
            'servico'       => (string) ($dados['servico'] ?? ''),
        ]);

        return redirect()->to('/encaminhamentos/' . $id)
            ->with('sucesso', 'Encaminhamento registrado.');
    }

    /**
     * Encaminhamentos que ainda precisam de acompanhamento.
     */
    public function pendentes()
    {
        return $this->consultar('pendentes');
    }

    /**
     * Encaminhamentos concluídos.
     */
    public function concluidos()
    {
        return $this->consultar('concluidos');
    }

    private function consultar(string $visao)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'q'               => trim((string) $this->request->getGet('q')),
            'orgao'           => (string) $this->request->getGet('orgao'),
            'servico'         => (string) $this->request->getGet('servico'),
            'profissional_id' => max(0, (int) $this->request->getGet('profissional')),
            'status'          => (string) $this->request->getGet('status'),
            'prioridade'      => (string) $this->request->getGet('prioridade'),
            'data_de'         => (string) $this->request->getGet('data_de'),
            'data_ate'        => (string) $this->request->getGet('data_ate'),
            'prazo'           => (string) $this->request->getGet('prazo'),
        ];

        $model = $this->encaminhamentoModel->comDados();

        if ($visao === 'pendentes') {
            $model->whereIn('encaminhamentos.status', EncaminhamentoModel::STATUS_PENDENTES);
        } else {
            $model->where('encaminhamentos.status', 'concluido');
        }

        if ($filtros['q'] !== '') {
            $termo = $filtros['q'];
            $model->groupStart()
                ->like('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }
        if ($filtros['orgao'] !== '' && array_key_exists($filtros['orgao'], ConfiguracaoListaModel::todasCategoria('orgao'))) {
            $model->where('encaminhamentos.orgao', $filtros['orgao']);
        }
        if ($filtros['servico'] !== '' && array_key_exists($filtros['servico'], EncaminhamentoModel::SERVICOS)) {
            $model->where('encaminhamentos.servico', $filtros['servico']);
        }
        if ($filtros['profissional_id'] > 0) {
            $model->where('encaminhamentos.profissional_id', $filtros['profissional_id']);
        }
        if ($filtros['status'] !== '' && array_key_exists($filtros['status'], ConfiguracaoListaModel::todasCategoria('status_encaminhamento'))) {
            $model->where('encaminhamentos.status', $filtros['status']);
        }
        if ($filtros['prioridade'] !== '' && array_key_exists($filtros['prioridade'], ConfiguracaoListaModel::todasCategoria('prioridade'))) {
            $model->where('encaminhamentos.prioridade', $filtros['prioridade']);
        }
        if ($filtros['data_de'] !== '') {
            $model->where('encaminhamentos.data_hora >=', $filtros['data_de'] . ' 00:00:00');
        }
        if ($filtros['data_ate'] !== '') {
            $model->where('encaminhamentos.data_hora <=', $filtros['data_ate'] . ' 23:59:59');
        }
        if ($filtros['prazo'] !== '') {
            $model->where('encaminhamentos.prazo', $filtros['prazo']);
        }

        $encaminhamentos = $model->orderBy('encaminhamentos.data_hora', 'DESC')->paginate(15);

        $data = [
            'titulo'         => $visao === 'pendentes' ? 'Encaminhamentos pendentes' : 'Encaminhamentos concluídos',
            'visao'          => $visao,
            'encaminhamentos' => $encaminhamentos,
            'pager'          => $this->encaminhamentoModel->pager,
            'filtros'        => $filtros,
            'equipe'         => $this->equipeAtiva(),
            'status'         => ConfiguracaoListaModel::todasCategoria('status_encaminhamento'),
            'orgaos'         => ConfiguracaoListaModel::todasCategoria('orgao'),
            'servicos'       => EncaminhamentoModel::SERVICOS,
            'prioridades'    => $this->encaminhamentoModel->prioridades(),
        ];

        return view('encaminhamentos/lista', $data);
    }

    /**
     * Ficha completa do encaminhamento, com ações de acompanhamento de status.
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $encaminhamento = $this->encaminhamentoModel->comDados()
            ->where('encaminhamentos.id', $id)
            ->first();

        if ($encaminhamento === null) {
            return redirect()->to('/encaminhamentos/pendentes')->with('erro', 'Encaminhamento não encontrado.');
        }

        $data = [
            'titulo'         => 'Encaminhamento #' . $id,
            'encaminhamento' => $encaminhamento,
            'status'         => ConfiguracaoListaModel::todasCategoria('status_encaminhamento'),
            'fluxoStatus'    => EncaminhamentoModel::FLUXO_STATUS,
            'orgaos'         => ConfiguracaoListaModel::todasCategoria('orgao'),
            'servicos'       => EncaminhamentoModel::SERVICOS,
            'prioridades'    => $this->encaminhamentoModel->prioridades(),
        ];

        return view('encaminhamentos/detalhes', $data);
    }

    /**
     * Atualiza o status conforme o fluxo (Pendente → Encaminhado → Em
     * atendimento → Aguardando retorno → Concluído; Cancelado quando preciso).
     * Ao concluir, registra a data de conclusão.
     */
    public function mudarStatus(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->encaminhamentoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/encaminhamentos/pendentes')->with('erro', 'Encaminhamento não encontrado.');
        }

        $novoStatus = (string) $this->request->getPost('status');
        if (! array_key_exists($novoStatus, ConfiguracaoListaModel::todasCategoria('status_encaminhamento'))) {
            return redirect()->back()->with('erro', 'Situação inválida para o encaminhamento.');
        }

        $permitidos = EncaminhamentoModel::FLUXO_STATUS[$atual['status']] ?? [];
        if (! in_array($novoStatus, $permitidos, true)) {
            return redirect()->back()->with('erro', 'Transição de status não permitida para este encaminhamento.');
        }

        $dados = ['status' => $novoStatus];
        if ($novoStatus === 'concluido') {
            $dados['data_conclusao'] = $atual['data_conclusao'] ?? date('Y-m-d H:i:s');
        } elseif ((string) $atual['status'] === 'concluido') {
            $dados['data_conclusao'] = null;
        }

        $this->encaminhamentoModel->update($id, $dados);

        $this->auditar('STATUS_CHANGE', 'encaminhamentos', $id, 'encaminhamento', (string) ($atual['orgao'] ?? ''), [
            'situacao_de'   => (string) ($atual['status'] ?? ''),
            'situacao_para' => $novoStatus,
        ]);

        return redirect()->to('/encaminhamentos/' . $id)->with('sucesso', 'Status do encaminhamento atualizado.');
    }

    /**
     * Monta os campos editáveis do formulário a partir do POST.
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        return [
            'status'         => 'pendente',
            'instituicao_id' => max(0, (int) $this->request->getPost('instituicao_id')),
            'servico'    => $limpar($this->request->getPost('servico')),
            'motivo'     => $limpar($this->request->getPost('motivo')),
            'prioridade' => $limpar($this->request->getPost('prioridade')) ?? 'media',
            'prazo'      => $limpar($this->request->getPost('prazo')),
            'observacoes' => $limpar($this->request->getPost('observacoes')),
        ];
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

    private function linhaAtendimento(array $a): array
    {
        return [
            'id'               => (int) $a['id'],
            'protocolo'        => (string) $a['protocolo'],
            'data_hora'        => (string) $a['data_hora'],
            'prioridade'       => (string) $a['prioridade'],
            'situacao'         => (string) $a['situacao'],
            'mulher_id'        => (int) $a['mulher_id'],
            'mulher_nome'      => (string) $a['mulher_nome'],
            'mulher_cpf'       => (string) $a['mulher_cpf'],
            'mulher_bairro'    => (string) $a['mulher_bairro'],
            'mulher_municipio' => (string) $a['mulher_municipio'],
            'data_hora_formatted' => ! empty($a['data_hora'])
                ? date('d/m/Y H:i', strtotime((string) $a['data_hora']))
                : '',
        ];
    }
}