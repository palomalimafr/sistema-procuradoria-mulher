<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\AtendimentoModel;
use App\Models\CompromissoModel;
use App\Models\EncaminhamentoModel;
use App\Models\MulherModel;
use App\Models\UserModel;

class Agenda extends BaseController
{
    protected $compromissoModel;

    public function __construct()
    {
        $this->compromissoModel = new CompromissoModel();
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
     * Agenda com as visualizações dia, semana e mês sobre os MESMOS compromissos.
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $visao = in_array((string) $this->request->getGet('visao'), ['semana', 'mes'], true)
            ? (string) $this->request->getGet('visao')
            : 'dia';

        $data = (string) $this->request->getGet('data');
        if ($data === '' || ! $this->dataValida($data)) {
            $data = date('Y-m-d');
        }

        $filtros = [
            'q'               => trim((string) $this->request->getGet('q')),
            'tipo'            => (string) $this->request->getGet('tipo'),
            'profissional_id' => max(0, (int) $this->request->getGet('profissional')),
            'status'          => (string) $this->request->getGet('status'),
        ];

        [$de, $ate] = $this->periodo($visao, $data);

        $model = $this->compromissoModel->comDados();

        $model->where('compromissos.data >=', $de)->where('compromissos.data <=', $ate);

        if ($filtros['q'] !== '') {
            $termo = $filtros['q'];
            $model->groupStart()
                ->like('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }
        if ($filtros['tipo'] !== '' && array_key_exists($filtros['tipo'], CompromissoModel::TIPOS)) {
            $model->where('compromissos.tipo', $filtros['tipo']);
        }
        if ($filtros['profissional_id'] > 0) {
            $model->where('compromissos.profissional_id', $filtros['profissional_id']);
        }
        if ($filtros['status'] !== '' && array_key_exists($filtros['status'], CompromissoModel::STATUS)) {
            $model->where('compromissos.status', $filtros['status']);
        }

        $agenda = $model->orderBy('compromissos.data', 'ASC')
            ->orderBy('compromissos.hora_inicio', 'ASC')
            ->findAll();

        $porDia = [];
        foreach ($agenda as $c) {
            $porDia[$c['data']][] = $c;
        }

        $data = [
            'titulo'     => 'Agenda',
            'agenda'     => $agenda,
            'porDia'     => $porDia,
            'visao'      => $visao,
            'hoje'       => date('Y-m-d'),
            'data'       => $data,
            'de'         => $de,
            'ate'        => $ate,
            'filtros'    => $filtros,
            'tipos'      => CompromissoModel::TIPOS,
            'status'     => CompromissoModel::STATUS,
            'equipe'     => $this->equipeAtiva(),
        ];

        return view('agenda/index', $data);
    }

    /**
     * Formulário de novo compromisso, com vínculos opcionais pré-selecionados.
     */
    public function novo()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulher = null;
        $atendimento = null;
        $encaminhamento = null;

        $encaminhamentoId = (int) $this->request->getGet('encaminhamento_id');
        if ($encaminhamentoId > 0) {
            $e = (new EncaminhamentoModel())->comDados()
                ->where('encaminhamentos.id', $encaminhamentoId)
                ->first();
            if ($e !== null) {
                $encaminhamento = $this->linhaEncaminhamento($e);
                $mulher = $this->mulherPreselecionada((int) $e['mulher_id']);
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
            }
        }

        $mulherId = (int) $this->request->getGet('mulher_id');
        if ($mulherId > 0 && $mulher === null) {
            $mulher = $this->mulherPreselecionada($mulherId);
        }

        $data = [
            'titulo'          => 'Novo Compromisso',
            'editando'        => false,
            'compromisso'     => null,
            'mulherPreselecionada' => $mulher,
            'atendimentoPreselecionado' => $atendimento,
            'encaminhamentoPreselecionado' => $encaminhamento,
            'contexto'        => (new ContextoMulher())->montar((int) ($mulher['id'] ?? 0)),
            'usuarioId'       => (int) session()->get('usuario_id'),
            'usuarioNome'     => (string) session()->get('usuario_nome'),
            'equipe'          => $this->equipeAtiva(),
            'tipos'           => CompromissoModel::TIPOS,
            'status'          => CompromissoModel::STATUS,
            'hoje'            => date('Y-m-d'),
        ];

        return view('agenda/form', $data);
    }

    /**
     * Registra o compromisso (status inicial: Agendado).
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $dados = $this->dadosDoPost();
        $dados['status'] = 'agendado';

        $erro = $this->validarVinculos($dados);
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        if (! $this->compromissoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->compromissoModel->errors());
        }

        $id = (int) $this->compromissoModel->getInsertID();

        $this->auditar('CREATE', 'agenda', $id, 'compromisso', null, [
            'tipo' => (string) ($dados['tipo'] ?? ''),
            'data' => (string) ($dados['data'] ?? ''),
            'hora' => (string) ($dados['hora_inicio'] ?? ''),
        ]);

        return redirect()->to('/agenda/' . $id)
            ->with('sucesso', 'Compromisso agendado.');
    }

    /**
     * Ficha completa do compromisso, com ações de status e reagendamento.
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $compromisso = $this->compromissoModel->comDados()
            ->where('compromissos.id', $id)
            ->first();

        if ($compromisso === null) {
            return redirect()->to('/agenda')->with('erro', 'Compromisso não encontrado.');
        }

        $reagendadoPara = null;
        if (! empty($compromisso['reagendado_para_id'])) {
            $reagendadoPara = $this->compromissoModel->comDados()
                ->where('compromissos.id', (int) $compromisso['reagendado_para_id'])
                ->first();
        }

        $data = [
            'titulo'       => 'Compromisso #' . $id,
            'compromisso'  => $compromisso,
            'tipos'        => CompromissoModel::TIPOS,
            'status'       => CompromissoModel::STATUS,
            'fluxoStatus'  => CompromissoModel::FLUXO_STATUS,
            'reagendarDe'  => CompromissoModel::REAGENDAR_DE,
            'reagendadoPara' => $reagendadoPara,
        ];

        return view('agenda/detalhes', $data);
    }

    /**
     * Formulário de edição do compromisso.
     */
    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $compromisso = $this->compromissoModel->comDados()
            ->where('compromissos.id', $id)
            ->first();

        if ($compromisso === null) {
            return redirect()->to('/agenda')->with('erro', 'Compromisso não encontrado.');
        }

        $data = [
            'titulo'          => 'Editar Compromisso #' . $id,
            'editando'        => true,
            'compromisso'     => $compromisso,
            'mulherPreselecionada' => $this->mulherPreselecionada((int) $compromisso['mulher_id']),
            'atendimentoPreselecionado' => ! empty($compromisso['atendimento_id'])
                ? $this->atendimentoPreselecionado((int) $compromisso['atendimento_id'])
                : null,
            'encaminhamentoPreselecionado' => ! empty($compromisso['encaminhamento_id'])
                ? $this->linhaEncaminhamento([
                    'id'                  => (int) $compromisso['encaminhamento_id'],
                    'mulher_nome'         => $compromisso['mulher_nome'] ?? '',
                    'encaminhamento_orgao' => $compromisso['encaminhamento_orgao'] ?? '',
                ])
                : null,
            'usuarioId'       => (int) session()->get('usuario_id'),
            'usuarioNome'     => (string) session()->get('usuario_nome'),
            'equipe'          => $this->equipeAtiva(),
            'tipos'           => CompromissoModel::TIPOS,
            'status'          => CompromissoModel::STATUS,
            'hoje'            => date('Y-m-d'),
        ];

        return view('agenda/form', $data);
    }

    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->compromissoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/agenda')->with('erro', 'Compromisso não encontrado.');
        }

        $dados = $this->dadosDoPost();

        // "Reagendado" só por meio da ação dedicada (mantém o histórico).
        $postStatus = (string) $this->request->getPost('status');
        $dados['status'] = $postStatus !== '' && $postStatus !== 'reagendado'
            ? $postStatus
            : $atual['status'];

        $erro = $this->validarVinculos($dados);
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        if (! $this->compromissoModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('erros', $this->compromissoModel->errors());
        }

        $this->auditar('UPDATE', 'agenda', $id, 'compromisso', null, []);

        return redirect()->to('/agenda/' . $id)->with('sucesso', 'Compromisso atualizado.');
    }

    /**
     * Atualização simples de status conforme o fluxo (sem criar novo registro).
     */
    public function mudarStatus(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->compromissoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/agenda')->with('erro', 'Compromisso não encontrado.');
        }

        $novoStatus = (string) $this->request->getPost('status');
        if (! array_key_exists($novoStatus, CompromissoModel::STATUS)) {
            return redirect()->back()->with('erro', 'Situação inválida para o compromisso.');
        }

        $permitidos = CompromissoModel::FLUXO_STATUS[$atual['status']] ?? [];
        if (! in_array($novoStatus, $permitidos, true)) {
            return redirect()->back()->with('erro', 'Transição de status não permitida para este compromisso.');
        }

        $this->compromissoModel->update($id, ['status' => $novoStatus]);

        $this->auditar('STATUS_CHANGE', 'agenda', $id, 'compromisso', null, [
            'situacao_de'   => (string) ($atual['status'] ?? ''),
            'situacao_para' => $novoStatus,
        ]);

        return redirect()->to('/agenda/' . $id)->with('sucesso', 'Status do compromisso atualizado.');
    }

    /**
     * Reagendamento: mantém o registro original e cria o novo compromisso,
     * vinculando o original ao novo via reagendado_para_id.
     */
    public function reagendar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->compromissoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/agenda')->with('erro', 'Compromisso não encontrado.');
        }

        if (! in_array($atual['status'], CompromissoModel::REAGENDAR_DE, true)) {
            return redirect()->back()->with('erro', 'Este compromisso não pode ser reagendado.');
        }

        $novaData = trim((string) $this->request->getPost('data'));
        $novaHora = trim((string) $this->request->getPost('hora_inicio'));
        if ($novaData === '' || $novaHora === '') {
            return redirect()->back()->with('erro', 'Informe a nova data e o novo horário para reagendar.');
        }

        $novo = [
            'mulher_id'        => $atual['mulher_id'] !== null ? (int) $atual['mulher_id'] : null,
            'atendimento_id'   => $atual['atendimento_id'] !== null ? (int) $atual['atendimento_id'] : null,
            'encaminhamento_id' => $atual['encaminhamento_id'] !== null ? (int) $atual['encaminhamento_id'] : null,
            'acolhimento_id'   => $atual['acolhimento_id'] !== null ? (int) $atual['acolhimento_id'] : null,
            'tipo'             => (string) $atual['tipo'],
            'data'             => $novaData,
            'hora_inicio'      => $this->normalizarHora($novaHora),
            'hora_fim'         => null,
            'profissional_id'  => $atual['profissional_id'] !== null ? (int) $atual['profissional_id'] : null,
            'observacoes'      => $atual['observacoes'] ?? null,
            'status'           => 'agendado',
        ];

        if (! $this->compromissoModel->save($novo)) {
            return redirect()->back()->withInput()->with('erros', $this->compromissoModel->errors());
        }

        $novoId = (int) $this->compromissoModel->getInsertID();
        $this->compromissoModel->update($id, [
            'status'            => 'reagendado',
            'reagendado_para_id' => $novoId,
        ]);

        $this->auditar('UPDATE', 'agenda', $id, 'compromisso', null, [
            'situacao_para'      => 'reagendado',
            'novo_compromisso_id' => $novoId,
        ]);
        $this->auditar('CREATE', 'agenda', $novoId, 'compromisso', null, [
            'tipo' => (string) ($atual['tipo'] ?? ''),
            'data' => $novaData,
        ]);

        return redirect()->to('/agenda/' . $id)
            ->with('sucesso', 'Compromisso reagendado. Foi criado o novo compromisso #' . $novoId . '.');
    }

    /**
     * Localiza encaminhamentos existentes (AJAX) para o novo compromisso.
     */
    public function buscarEncaminhamentos()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo = trim((string) $this->request->getGet('term'));

        $model = (new EncaminhamentoModel())->comDados();
        if ($termo !== '') {
            $model->groupStart()
                ->like('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->orLike('encaminhamentos.id', $termo)
                ->groupEnd();
        }

        $encaminhamentos = $model->orderBy('encaminhamentos.data_hora', 'DESC')->limit(15)->findAll();

        $resultado = array_map(
            fn (array $e): array => $this->linhaEncaminhamento($e),
            $encaminhamentos
        );

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['encaminhamentos' => $resultado]);
    }

    /**
     * Valida os vínculos opcionais do compromisso (mulher/atendimento/encaminhamento).
     *
     * @return array{0: string, 1: string}|null
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

    /**
     * Monta os campos do formulário a partir do POST.
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        return [
            'mulher_id'        => $limpar($this->request->getPost('mulher_id')) === null ? null : (int) $this->request->getPost('mulher_id'),
            'atendimento_id'   => $limpar($this->request->getPost('atendimento_id')) === null ? null : (int) $this->request->getPost('atendimento_id'),
            'encaminhamento_id' => $limpar($this->request->getPost('encaminhamento_id')) === null ? null : (int) $this->request->getPost('encaminhamento_id'),
            'tipo'             => $limpar($this->request->getPost('tipo')),
            'data'             => $limpar($this->request->getPost('data')),
            'hora_inicio'      => $this->normalizarHora($this->request->getPost('hora_inicio')),
            'hora_fim'         => $limpar($this->request->getPost('hora_fim')) === null ? null : $this->normalizarHora($this->request->getPost('hora_fim')),
            'profissional_id'  => max(0, (int) $this->request->getPost('profissional_id')) > 0 ? (int) $this->request->getPost('profissional_id') : null,
            'observacoes'      => $limpar($this->request->getPost('observacoes')),
        ];
    }

    private function normalizarHora($valor): string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '';
        }

        $junto = preg_replace('/\s+/', '', $valor);
        if ($junto === null || $junto === '') {
            return '';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $junto)) {
            return $junto . ':00';
        }

        return $junto;
    }

    private function dataValida(string $data): bool
    {
        return (bool) strtotime($data) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;
    }

    private function periodo(string $visao, string $data): array
    {
        $ts = strtotime($data);

        if ($visao === 'mes') {
            return [
                date('Y-m-01', $ts),
                date('Y-m-t', $ts),
            ];
        }

        if ($visao === 'semana') {
            $dow = (int) date('N', $ts);
            $inicio = strtotime('-' . ($dow - 1) . ' days', $ts);

            return [
                date('Y-m-d', $inicio),
                date('Y-m-d', strtotime('+6 days', $inicio)),
            ];
        }

        return [$data, $data];
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