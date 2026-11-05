<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\AcolhimentoHistoricoModel;
use App\Models\AcolhimentoModel;
use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\UserModel;

class Acolhimentos extends BaseController
{
    protected $acolhimentoModel;

    public function __construct()
    {
        $this->acolhimentoModel = new AcolhimentoModel();
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
     * Consulta de acolhimentos registrados (seção 10 da especificação).
     */
    public function consulta()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'q'               => trim((string) $this->request->getGet('q')),
            'status'          => (string) $this->request->getGet('status'),
            'profissional_id' => max(0, (int) $this->request->getGet('profissional')),
            'data_de'         => (string) $this->request->getGet('data_de'),
            'data_ate'        => (string) $this->request->getGet('data_ate'),
        ];

        $model = $this->acolhimentoModel->comDados();

        if ($filtros['q'] !== '') {
            $termo = $filtros['q'];
            $model->groupStart()
                ->like('atendimentos.protocolo', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->groupEnd();
        }
        if ($filtros['status'] !== '' && array_key_exists($filtros['status'], ConfiguracaoListaModel::todasCategoria('status_acolhimento'))) {
            $model->where('acolhimentos.status', $filtros['status']);
        }
        if ($filtros['profissional_id'] > 0) {
            $model->where('acolhimentos.profissional_id', $filtros['profissional_id']);
        }
        if ($filtros['data_de'] !== '') {
            $model->where('acolhimentos.data_hora >=', $filtros['data_de'] . ' 00:00:00');
        }
        if ($filtros['data_ate'] !== '') {
            $model->where('acolhimentos.data_hora <=', $filtros['data_ate'] . ' 23:59:59');
        }

        $acolhimentos = $model->orderBy('acolhimentos.data_hora', 'DESC')->paginate(10);

        $data = [
            'titulo'       => 'Acolhimentos',
            'acolhimentos' => $acolhimentos,
            'pager'        => $this->acolhimentoModel->pager,
            'filtros'      => $filtros,
            'equipe'       => $this->equipeAtiva(),
            'status'       => ConfiguracaoListaModel::todasCategoria('status_acolhimento'),
            'riscos'       => AcolhimentoModel::RISCOS,
        ];

        return view('acolhimentos/lista', $data);
    }

    /**
     * Formulário de novo acolhimento, com busca do atendimento existente.
     */
    public function novo()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atendimentoPreselecionado = null;
        $mulherContexto            = 0;
        $acolhimentosEmAberto      = [];

        $atendimentoId = (int) $this->request->getGet('atendimento_id');
        if ($atendimentoId > 0) {
            $atendimento = (new AtendimentoModel())->comDados()
                ->where('atendimentos.id', $atendimentoId)
                ->first();
            if ($atendimento !== null) {
                $atendimentoPreselecionado = $this->linhaAtendimento($atendimento);
                $mulherContexto            = (int) ($atendimento['mulher_id'] ?? 0);
            }
        }

        $mulherId = (int) $this->request->getGet('mulher_id');
        if ($mulherId > 0 && $atendimentoPreselecionado === null) {
            $mulherContexto = $mulherId;
            $emAberto = (new AtendimentoModel())->unicoAbertoPorMulher($mulherId);
            if ($emAberto !== null) {
                $atendimentoPreselecionado = $this->linhaAtendimento($emAberto);
            }
        }

        if ($mulherContexto > 0) {
            $atendimentosDaMulher = array_column(
                (new AtendimentoModel())->select('id')
                    ->where('mulher_id', $mulherContexto)
                    ->findAll(),
                'id'
            );

            if ($atendimentosDaMulher !== []) {
                $acolhimentosEmAberto = (new AcolhimentoModel())
                    ->whereIn('atendimento_id', $atendimentosDaMulher)
                    ->where('status', 'em_andamento')
                    ->orderBy('data_hora', 'DESC')
                    ->findAll();
            }
        }

        $data = [
            'titulo'                   => 'Novo Acolhimento',
            'editando'                 => false,
            'acolhimento'              => null,
            'atendimentoPreselecionado' => $atendimentoPreselecionado,
            'acolhimentosEmAberto'     => $acolhimentosEmAberto,
            'contexto'                 => (new ContextoMulher())->montar($mulherContexto),
            'usuarioNome'              => (string) session()->get('usuario_nome'),
            'status'                   => ConfiguracaoListaModel::opcoesCategoria('status_acolhimento'),
            'riscos'                   => AcolhimentoModel::RISCOS,
            'necessidades'             => $this->acolhimentoModel->necessidades(),
            'hoje'                     => date('Y-m-d\TH:i'),
        ];

        return view('acolhimentos/form', $data);
    }

    /**
     * Localiza atendimentos existentes (AJAX) para o novo acolhimento.
     */
    public function buscarAtendimentos()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo     = trim((string) $this->request->getGet('term'));
        $mulherId  = (int) $this->request->getGet('mulher_id');

        $model = (new AtendimentoModel())->comDados();
        if ($termo !== '') {
            $model->groupStart()
                ->like('atendimentos.protocolo', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }
        if ($mulherId > 0) {
            $model->where('dados_mulher.id', $mulherId);
        }

        $atendimentos = $model->orderBy('atendimentos.data_hora', 'DESC')->limit(15)->findAll();

        $resultado = array_map(
            fn (array $a): array => $this->linhaAtendimento($a),
            $atendimentos
        );

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['atendimentos' => $resultado]);
    }

    /**
     * Registra o acolhimento. O profissional responsável é SEMPRE o usuário
     * autenticado (não é permitido informar outra pessoa).
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atendimentoId = (int) $this->request->getPost('atendimento_id');

        if ($atendimentoId <= 0) {
            return redirect()->back()->withInput()->with('erros', ['atendimento_id' => 'Localize e selecione o atendimento vinculado.']);
        }

        $atendimento = (new AtendimentoModel())->find($atendimentoId);
        if ($atendimento === null) {
            return redirect()->back()->withInput()->with('erros', ['atendimento_id' => 'O atendimento selecionado não existe mais.']);
        }

        $dados = $this->dadosDoPost();
        $dados['atendimento_id'] = $atendimentoId;
        $dados['data_hora'] = ! empty($this->request->getPost('data_hora'))
            ? date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('data_hora')))
            : date('Y-m-d H:i:s');
        $dados['profissional_id'] = (int) session()->get('usuario_id') > 0 ? (int) session()->get('usuario_id') : null;

        if (! $this->acolhimentoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->acolhimentoModel->errors());
        }

        (new AcolhimentoHistoricoModel())->registrar(
            (int) $this->acolhimentoModel->getInsertID(),
            null,
            'em_andamento',
            (int) session()->get('usuario_id')
        );

        $id = (int) $this->acolhimentoModel->getInsertID();

        $this->auditar('CREATE', 'acolhimentos', $id, 'acolhimento', null, [
            'atendimento_id' => $atendimentoId,
            'situacao'       => 'em_andamento',
        ]);

        return redirect()->to('/acolhimentos/' . $id)
            ->with('sucesso', 'Acolhimento registrado. As necessidades identificadas poderão gerar encaminhamentos e tarefas em etapa futura.');
    }

    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $acolhimento = $this->acolhimentoModel->comDados()
            ->where('acolhimentos.id', $id)
            ->first();

        if ($acolhimento === null) {
            return redirect()->to('/acolhimentos')->with('erro', 'Acolhimento não encontrado.');
        }

        $avaliacao = (new \App\Models\AvaliacaoRiscoModel())->porAcolhimento($id);

        $data = [
            'titulo'        => 'Acolhimento #' . $id,
            'acolhimento'   => $acolhimento,
            'necessidades'  => $this->acolhimentoModel->necessidades(),
            'necessidadesMarcadas' => $this->acolhimentoModel->necessidadesMarcadas($acolhimento),
            'status'        => ConfiguracaoListaModel::todasCategoria('status_acolhimento'),
            'riscos'        => AcolhimentoModel::RISCOS,
            'historico'     => (new AcolhimentoHistoricoModel())->listarPorAcolhimento($id),
            'avaliacao'     => $avaliacao,
            'avaliacaoPerguntas' => $avaliacao !== null
                ? (new \App\Models\AvaliacaoRiscoModel())->perguntas()
                : [],
        ];

        return view('acolhimentos/detalhes', $data);
    }

    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $acolhimento = $this->acolhimentoModel->comDados()
            ->where('acolhimentos.id', $id)
            ->first();

        if ($acolhimento === null) {
            return redirect()->to('/acolhimentos')->with('erro', 'Acolhimento não encontrado.');
        }

        $data = [
            'titulo'                   => 'Editar Acolhimento #' . $id,
            'editando'                 => true,
            'acolhimento'              => $acolhimento,
            'atendimentoPreselecionado' => $this->linhaAtendimento($acolhimento),
            'usuarioNome'              => (string) session()->get('usuario_nome'),
            'status'                   => ConfiguracaoListaModel::opcoesCategoria('status_acolhimento'),
            'riscos'                   => AcolhimentoModel::RISCOS,
            'necessidades'             => $this->acolhimentoModel->necessidades(),
            'hoje'                     => date('Y-m-d\TH:i'),
        ];

        return view('acolhimentos/form', $data);
    }

    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->acolhimentoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/acolhimentos')->with('erro', 'Acolhimento não encontrado.');
        }

        $dados = $this->dadosDoPost();
        $dados['status'] = (string) $this->request->getPost('status');
        $dados['data_hora'] = ! empty($this->request->getPost('data_hora'))
            ? date('Y-m-d H:i:s', strtotime((string) $this->request->getPost('data_hora')))
            : $atual['data_hora'];

        if (! array_key_exists($dados['status'], ConfiguracaoListaModel::todasCategoria('status_acolhimento'))) {
            return redirect()->back()->withInput()->with('erros', ['status' => 'Selecione um status válido.']);
        }

        if ($dados['status'] === 'concluido') {
            $dados['data_conclusao'] = $atual['data_conclusao'] ?? date('Y-m-d H:i:s');
        } else {
            $dados['data_conclusao'] = null;
        }

        if (! $this->acolhimentoModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('erros', $this->acolhimentoModel->errors());
        }

        $acolhimentoAudit = [];
        if ((string) $atual['status'] !== $dados['status']) {
            $acolhimentoAudit['situacao_de']   = (string) ($atual['status'] ?? '');
            $acolhimentoAudit['situacao_para'] = (string) $dados['status'];
        }
        $this->auditar('UPDATE', 'acolhimentos', $id, 'acolhimento', null, $acolhimentoAudit);

        if ((string) $atual['status'] !== $dados['status']) {
            (new AcolhimentoHistoricoModel())->registrar(
                $id,
                $atual['status'] === null ? null : (string) $atual['status'],
                $dados['status'],
                (int) session()->get('usuario_id')
            );
        }

        return redirect()->to('/acolhimentos/' . $id)->with('sucesso', 'Acolhimento atualizado.');
    }

    public function concluir(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->acolhimentoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/acolhimentos')->with('erro', 'Acolhimento não encontrado.');
        }

        if ($atual['status'] === 'concluido') {
            return redirect()->back()->with('erro', 'Este acolhimento já está concluído.');
        }

        $this->acolhimentoModel->update($id, [
            'status'          => 'concluido',
            'data_conclusao'  => date('Y-m-d H:i:s'),
        ]);

        $this->auditar('CLOSE', 'acolhimentos', $id, 'acolhimento', null, []);

        (new AcolhimentoHistoricoModel())->registrar(
            $id,
            'em_andamento',
            'concluido',
            (int) session()->get('usuario_id')
        );

        return redirect()->to('/acolhimentos/' . $id)->with('sucesso', 'Acolhimento concluído.');
    }

    /**
     * Monta os campos editáveis do formulário a partir do POST.
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;
        $bool = static fn ($valor) => ! empty($valor) ? 1 : 0;

        return [
            'status'                  => 'em_andamento',
            'motivo_procura'          => $limpar($this->request->getPost('motivo_procura')),
            'situacao_apresentada'    => $limpar($this->request->getPost('situacao_apresentada')),
            'necessidades_imediatas'  => $limpar($this->request->getPost('necessidades_imediatas')),
            'necessidade_protecao'    => $bool($this->request->getPost('necessidade_protecao')),
            'necessidade_juridica'    => $bool($this->request->getPost('necessidade_juridica')),
            'necessidade_psicologica' => $bool($this->request->getPost('necessidade_psicologica')),
            'necessidade_social'      => $bool($this->request->getPost('necessidade_social')),
            'necessidade_saude'       => $bool($this->request->getPost('necessidade_saude')),
            'necessidade_seguranca'   => $bool($this->request->getPost('necessidade_seguranca')),
            'necessidade_abrigo'      => $bool($this->request->getPost('necessidade_abrigo')),
            'outras_necessidades'     => $limpar($this->request->getPost('outras_necessidades')),
            'risco_identificado'      => $limpar($this->request->getPost('risco_identificado')),
            'observacoes'             => $limpar($this->request->getPost('observacoes')),
        ];
    }

    /**
     * Linha compacta de um atendimento para busca/seleção do acolhimento.
     * Aceita array oriundo de comDados() de atendimentos ou de acolhimentos.
     */
    private function linhaAtendimento(array $a): array
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
}