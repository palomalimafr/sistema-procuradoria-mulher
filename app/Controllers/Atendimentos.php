<?php

namespace App\Controllers;

use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\MulherModel;
use App\Models\PedidoSosModel;
use App\Models\UserModel;
use DateTime;
use DateTimeInterface;
use DateInterval;

class Atendimentos extends BaseController
{
    protected $atendimentoModel;

    public function __construct()
    {
        $this->atendimentoModel = new AtendimentoModel();
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

    public function novo()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherPreselecionada = null;
        $pedidoSos = null;
        $mulherId = (int) $this->request->getGet('mulher_id');

        // Direcionamento de um pedido SOS: a mulher vem do próprio pedido.
        $pedidoSosId = max(0, (int) $this->request->getGet('pedido_sos_id'));
        if ($pedidoSosId > 0) {
            $pedido = (new PedidoSosModel())->find($pedidoSosId);

            if ($pedido === null) {
                return redirect()->to('/pedidos-sos')->with('erro', 'Pedido SOS não encontrado.');
            }

            if ($pedido['status'] !== 'pendente') {
                return redirect()->to('/pedidos-sos/' . $pedidoSosId)
                    ->with('erro', 'Este pedido SOS já foi direcionado para um atendimento.');
            }

            $existente = $this->atendimentoModel->emAbertoPorMulher((int) $pedido['mulher_id']);
            if ($existente !== null) {
                return redirect()->to('/pedidos-sos/' . $pedidoSosId)
                    ->with('erro', 'Já existe um atendimento em andamento para esta mulher (protocolo ' . $existente['protocolo'] . ').');
            }

            $mulherId  = (int) $pedido['mulher_id'];
            $pedidoSos = [
                'id'        => $pedidoSosId,
                'data_hora' => $pedido['data_hora'],
            ];
        }

        if ($mulherId > 0) {
            $mulher = (new MulherModel())->find($mulherId);
            if ($mulher !== null) {
                $mulherPreselecionada = [
                    'id'   => (int) $mulher['id'],
                    'nome' => (string) $mulher['nome_completo'],
                ];
            }
        }

        $data = [
            'titulo'               => 'Novo Atendimento',
            'protocoloPrevisto'    => $this->atendimentoModel->proximoProtocolo(),
            'equipe'               => $this->equipeAtiva(),
            'formas'               => ConfiguracaoListaModel::opcoesCategoria('tipo_atendimento'),
            'origens'              => ConfiguracaoListaModel::opcoesCategoria('origem_atendimento'),
            'prioridades'          => ConfiguracaoListaModel::opcoesCategoria('prioridade'),
            'situacoes'            => ConfiguracaoListaModel::opcoesCategoria('status_atendimento'),
            'mulherPreselecionada' => $mulherPreselecionada,
            'pedidoSos'            => $pedidoSos,
        ];

        return view('atendimentos/novo', $data);
    }

    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherId = (int) $this->request->getPost('mulher_id');

        if ($mulherId <= 0) {
            return redirect()->back()->withInput()->with('erros', ['mulher_id' => 'Localize e selecione a mulher atendida.']);
        }

        if ((new MulherModel())->find($mulherId) === null) {
            return redirect()->back()->withInput()->with('erros', ['mulher_id' => 'A mulher selecionada não está mais cadastrada.']);
        }

        // Vínculo com um pedido SOS (quando o atendimento vem dessa origem).
        $pedidoSosId = max(0, (int) $this->request->getPost('pedido_sos_id'));
        if ($pedidoSosId > 0) {
            $pedidoSos = (new PedidoSosModel())->find($pedidoSosId);

            if ($pedidoSos === null) {
                return redirect()->back()->withInput()->with('erros', ['pedido_sos_id' => 'O pedido SOS de origem não foi encontrado.']);
            }

            if ($pedidoSos['status'] !== 'pendente') {
                return redirect()->to('/pedidos-sos/' . $pedidoSosId)
                    ->with('erro', 'Este pedido SOS já foi direcionado para um atendimento.');
            }

            if ((int) $pedidoSos['mulher_id'] !== $mulherId) {
                return redirect()->back()->withInput()->with('erros', ['mulher_id' => 'A mulher selecionada não corresponde à do pedido SOS.']);
            }

            $existente = $this->atendimentoModel->emAbertoPorMulher($mulherId);
            if ($existente !== null) {
                return redirect()->to('/pedidos-sos/' . $pedidoSosId)
                    ->with('erro', 'Já existe um atendimento em andamento para esta mulher (protocolo ' . $existente['protocolo'] . ').');
            }
        }

        $forma = (string) $this->request->getPost('forma_atendimento');
        $origem = (string) $this->request->getPost('origem');
        $prioridade = (string) $this->request->getPost('prioridade');

        if (! array_key_exists($forma, ConfiguracaoListaModel::todasCategoria('tipo_atendimento'))) {
            return redirect()->back()->withInput()->with('erros', ['forma_atendimento' => 'Selecione uma forma de atendimento válida.']);
        }

        if (! array_key_exists($origem, ConfiguracaoListaModel::todasCategoria('origem_atendimento'))) {
            return redirect()->back()->withInput()->with('erros', ['origem' => 'Selecione uma origem válida.']);
        }

        if (! array_key_exists($prioridade, ConfiguracaoListaModel::todasCategoria('prioridade'))) {
            return redirect()->back()->withInput()->with('erros', ['prioridade' => 'Selecione uma prioridade válida.']);
        }

        $dataHoraRaw = (string) $this->request->getPost('data_hora');
        $dataHora = ! empty($dataHoraRaw) ? date('Y-m-d H:i:s', strtotime($dataHoraRaw)) : date('Y-m-d H:i:s');

        if ($dataHora === false || $dataHoraRaw !== '' && strtotime($dataHoraRaw) === false) {
            return redirect()->back()->withInput()->with('erros', ['data_hora' => 'Informe uma data e hora válidas.']);
        }

        $profissionalId = (int) $this->request->getPost('profissional_id');
        if ($profissionalId <= 0) {
            $profissionalId = (int) session()->get('usuario_id');
        }

        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $dados = [
            'protocolo'          => $this->atendimentoModel->proximoProtocolo(),
            'data_hora'          => $dataHora,
            'mulher_id'          => $mulherId,
            'profissional_id'    => $profissionalId > 0 ? $profissionalId : null,
            'unidade_setor'      => $limpar($this->request->getPost('unidade_setor')),
            'forma_atendimento'  => $forma,
            'origem'             => $origem,
            'origem_outra'       => $origem === 'outros' ? $limpar($this->request->getPost('origem_outra')) : null,
            'motivo_atendimento' => $limpar($this->request->getPost('motivo_atendimento')),
            'classificacao'      => $limpar($this->request->getPost('classificacao')),
            'prioridade'         => $prioridade,
            'situacao'           => 'novo',
            'descricao_caso'     => $limpar($this->request->getPost('descricao_caso')),
            'observacoes'        => $limpar($this->request->getPost('observacoes')),
            'pedido_sos_id'      => $pedidoSosId > 0 ? $pedidoSosId : null,
        ];

        if (! $this->atendimentoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->atendimentoModel->errors());
        }

        $id = (int) $this->atendimentoModel->getInsertID();

        if ($pedidoSosId > 0) {
            (new PedidoSosModel())->marcarDirecionado($pedidoSosId);

            $this->auditar('STATUS_CHANGE', 'pedidos_sos', $pedidoSosId, 'pedido_sos', (string) $pedidoSosId, [
                'status_de'      => 'pendente',
                'status_para'    => 'direcionado',
                'atendimento_id' => $id,
            ]);
        }

        $this->auditar('CREATE', 'atendimentos', $id, 'atendimento', (string) ($dados['protocolo'] ?? ''), [
            'protocolo'     => (string) ($dados['protocolo'] ?? ''),
            'mulher_id'     => $mulherId,
            'prioridade'    => $prioridade,
            'pedido_sos_id' => $pedidoSosId > 0 ? $pedidoSosId : null,
        ]);

        if ($pedidoSosId > 0) {
            return redirect()->to('/atendimentos/fila')
                ->with('sucesso', 'Atendimento ' . $dados['protocolo'] . ' aberto a partir do pedido SOS e adicionado à fila.');
        }

        return redirect()->to('/atendimentos/fila')
            ->with('sucesso', 'Atendimento ' . $dados['protocolo'] . ' aberto e adicionado à fila.');
    }

    /**
     * Localiza mulheres cadastradas (AJAX) para seleção no novo atendimento.
     */
    public function buscarMulheres()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo = trim((string) $this->request->getGet('term'));

        $model = new MulherModel();
        if ($termo !== '') {
            $model->groupStart()
                ->like('nome_completo', $termo)
                ->orLike('nome_social', $termo)
                ->orLike('cpf', $termo)
                ->orLike('telefone', $termo)
                ->orLike('whatsapp', $termo)
                ->orLike('email', $termo)
                ->groupEnd();
        }

        $mulheres = $model->orderBy('nome_completo', 'ASC')->limit(15)->findAll();

        $resultado = array_map(static function (array $m): array {
            return [
                'id'               => (int) $m['id'],
                'nome'             => (string) $m['nome_completo'],
                'cpf'              => $m['cpf'] !== null ? (string) $m['cpf'] : '',
                'data_nascimento'  => $m['data_nascimento'] !== null ? (string) $m['data_nascimento'] : '',
                'bairro'           => $m['bairro'] !== null ? (string) $m['bairro'] : '',
                'municipio'        => $m['municipio'] !== null ? (string) $m['municipio'] : '',
            ];
        }, $mulheres);

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['mulheres' => $resultado]);
    }

    /**
     * Localiza atendimentos (AJAX) para os formulários que exigem o vínculo
     * com um atendimento já registrado (acolhimento, medida protetiva).
     *
     * O filtro por mulher é opcional: quando informado, restringe a busca
     * aos atendimentos daquela mulher, evitando que a profissional vincule
     * um registro à mulher errada.
     */
    public function buscar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo    = trim((string) $this->request->getGet('term'));
        $mulherId = (int) $this->request->getGet('mulher_id');

        $model = $this->atendimentoModel->comDados();

        if ($mulherId > 0) {
            $model->where('atendimentos.mulher_id', $mulherId);
        }

        if ($termo !== '') {
            $model->groupStart()
                ->like('atendimentos.protocolo', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }

        $atendimentos = $model->orderBy('atendimentos.data_hora', 'DESC')->limit(15)->findAll();

        $resultado = array_map(
            static fn (array $a): array => AtendimentoModel::linhaResumo($a),
            $atendimentos
        );

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['atendimentos' => $resultado]);
    }

    public function fila()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $model = $this->atendimentoModel->comDados();

        $filtros = $this->filtrosFila();

        $status = $filtros['status'];
        if ($status !== '' && array_key_exists($status, ConfiguracaoListaModel::todasCategoria('status_atendimento'))) {
            $model->where('atendimentos.situacao', $status);
        } else {
            $model->whereIn('atendimentos.situacao', ['novo', 'aguardando_atendimento']);
        }

        if ($filtros['prioridade'] !== '') {
            $model->where('atendimentos.prioridade', $filtros['prioridade']);
        }
        if ($filtros['origem'] !== '') {
            $model->where('atendimentos.origem', $filtros['origem']);
        }
        if ($filtros['profissional_id'] > 0) {
            $model->where('atendimentos.profissional_id', $filtros['profissional_id']);
        }
        if ($filtros['bairro'] !== '') {
            $model->like('dados_mulher.bairro', $filtros['bairro']);
        }

        if ($filtros['idade_min'] > 0) {
            $model->where('dados_mulher.data_nascimento <=', date('Y-m-d', strtotime('-' . $filtros['idade_min'] . ' years')));
        }
        if ($filtros['idade_max'] > 0) {
            $filtros['idade_max'] = (int) $filtros['idade_max'];
            $model->where('dados_mulher.data_nascimento >=', date('Y-m-d', strtotime('-' . ($filtros['idade_max'] + 1) . ' years')));
        }

        if ($filtros['data_de'] !== '') {
            $model->where('atendimentos.data_hora >=', $filtros['data_de'] . ' 00:00:00');
        }
        if ($filtros['data_ate'] !== '') {
            $model->where('atendimentos.data_hora <=', $filtros['data_ate'] . ' 23:59:59');
        }

        if ($filtros['atrasados']) {
            $model->where($this->condicaoAtrasadosSql(), null, false);
        }

        $model->orderBy($this->atendimentoModel->ordemPrioridadeSql(), 'ASC', false)
            ->orderBy('atendimentos.data_hora', 'ASC');

        $atendimentos = $model->paginate(10);

        $agora = new DateTime();
        foreach ($atendimentos as &$at) {
            $at = $this->complementarLinha($at, $agora);
        }
        unset($at);

        $data = [
            'titulo'      => 'Fila de Atendimento',
            'atendimentos' => $atendimentos,
            'pager'       => $this->atendimentoModel->pager,
            'filtros'     => $filtros,
            'equipe'      => $this->equipeAtiva(),
            'origens'     => ConfiguracaoListaModel::todasCategoria('origem_atendimento'),
            'prioridades' => ConfiguracaoListaModel::todasCategoria('prioridade'),
            'situacoes'   => ConfiguracaoListaModel::todasCategoria('status_atendimento'),
        ];

        return view('atendimentos/fila', $data);
    }

    public function emAndamento()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atendimentos = $this->atendimentoModel->comDados()
            ->where('atendimentos.situacao', 'em_andamento')
            ->orderBy('atendimentos.data_hora', 'ASC')
            ->paginate(10);

        $agora = new DateTime();
        foreach ($atendimentos as &$at) {
            $at = $this->complementarLinha($at, $agora);
        }
        unset($at);

        $data = [
            'titulo'       => 'Atendimentos em Andamento',
            'atendimentos' => $atendimentos,
            'pager'        => $this->atendimentoModel->pager,
            'prioridades'  => ConfiguracaoListaModel::todasCategoria('prioridade'),
            'situacoes'    => ConfiguracaoListaModel::todasCategoria('status_atendimento'),
            'origens'      => ConfiguracaoListaModel::todasCategoria('origem_atendimento'),
        ];

        return view('atendimentos/em_andamento', $data);
    }

    public function encerrados()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atendimentos = $this->atendimentoModel->comDados()
            ->where('atendimentos.situacao', 'encerrado')
            ->orderBy('atendimentos.data_encerramento', 'DESC')
            ->paginate(10);

        $agora = new DateTime();
        foreach ($atendimentos as &$at) {
            $at = $this->complementarLinha($at, $agora);
        }
        unset($at);

        $data = [
            'titulo'       => 'Atendimentos Encerrados',
            'atendimentos' => $atendimentos,
            'pager'        => $this->atendimentoModel->pager,
            'prioridades'  => ConfiguracaoListaModel::todasCategoria('prioridade'),
            'situacoes'    => ConfiguracaoListaModel::todasCategoria('status_atendimento'),
            'motivos'      => ConfiguracaoListaModel::todasCategoria('motivo_encerramento'),
        ];

        return view('atendimentos/encerrados', $data);
    }

    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atendimento = $this->atendimentoModel->comDados()
            ->where('atendimentos.id', $id)
            ->first();

        if ($atendimento === null) {
            return redirect()->to('/atendimentos/fila')->with('erro', 'Atendimento não encontrado.');
        }

        $data = [
            'titulo'       => 'Atendimento ' . $atendimento['protocolo'],
            'atendimento'  => $atendimento,
            'formas'       => ConfiguracaoListaModel::todasCategoria('tipo_atendimento'),
            'origens'      => ConfiguracaoListaModel::todasCategoria('origem_atendimento'),
            'prioridades'  => ConfiguracaoListaModel::todasCategoria('prioridade'),
            'situacoes'    => ConfiguracaoListaModel::todasCategoria('status_atendimento'),
            'motivos'      => ConfiguracaoListaModel::todasCategoria('motivo_encerramento'),
        ];

        return view('atendimentos/detalhes', $data);
    }

    public function iniciar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atendimento = $this->atendimentoModel->find($id);

        if ($atendimento === null) {
            return redirect()->to('/atendimentos/fila')->with('erro', 'Atendimento não encontrado.');
        }

        if ($atendimento['situacao'] === 'encerrado') {
            return redirect()->back()->with('erro', 'Este atendimento já foi encerrado.');
        }

        $this->atendimentoModel->update($id, ['situacao' => 'em_andamento']);

        $this->auditar('STATUS_CHANGE', 'atendimentos', $id, 'atendimento', (string) ($atendimento['protocolo'] ?? ''), [
            'situacao_de'   => (string) ($atendimento['situacao'] ?? ''),
            'situacao_para' => 'em_andamento',
        ]);

        return redirect()->to('/atendimentos/em-andamento')
            ->with('sucesso', 'Atendimento ' . $atendimento['protocolo'] . ' iniciado.');
    }

    public function encerrar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atendimento = $this->atendimentoModel->find($id);

        if ($atendimento === null) {
            return redirect()->to('/atendimentos/fila')->with('erro', 'Atendimento não encontrado.');
        }

        if ($atendimento['situacao'] === 'encerrado') {
            return redirect()->back()->with('erro', 'Este atendimento já foi encerrado.');
        }

        $motivo = (string) $this->request->getPost('motivo_encerramento');

        if (! array_key_exists($motivo, ConfiguracaoListaModel::todasCategoria('motivo_encerramento'))) {
            return redirect()->back()->with('erros', ['motivo_encerramento' => 'Selecione o motivo do encerramento.']);
        }

        $motivoOutra = null;
        if ($motivo === 'outros') {
            $motivoOutra = trim((string) $this->request->getPost('motivo_encerramento_outra'));
            if ($motivoOutra === '') {
                return redirect()->back()->with('erros', ['motivo_encerramento_outra' => 'Informe o detalhe do motivo de encerramento.']);
            }
        }

        $this->atendimentoModel->update($id, [
            'situacao'                 => 'encerrado',
            'data_encerramento'        => date('Y-m-d H:i:s'),
            'motivo_encerramento'      => $motivo,
            'motivo_encerramento_outra' => $motivoOutra,
        ]);

        $this->auditar('CLOSE', 'atendimentos', $id, 'atendimento', (string) ($atendimento['protocolo'] ?? ''), [
            'motivo' => $motivo,
            'outro'  => $motivoOutra,
        ]);

        return redirect()->to('/atendimentos/encerrados')
            ->with('sucesso', 'Atendimento ' . $atendimento['protocolo'] . ' encerrado.');
    }

    /**
     * Filtros da fila (GET).
     */
    private function filtrosFila(): array
    {
        return [
            'prioridade'     => (string) $this->request->getGet('prioridade'),
            'origem'         => (string) $this->request->getGet('origem'),
            'profissional_id'=> max(0, (int) $this->request->getGet('profissional')),
            'status'         => (string) $this->request->getGet('status'),
            'idade_min'      => max(0, (int) $this->request->getGet('idade_min')),
            'idade_max'      => max(0, (int) $this->request->getGet('idade_max')),
            'bairro'         => trim((string) $this->request->getGet('bairro')),
            'data_de'        => (string) $this->request->getGet('data_de'),
            'data_ate'       => (string) $this->request->getGet('data_ate'),
            'atrasados'      => (bool) $this->request->getGet('atrasados'),
        ];
    }

    private function condicaoAtrasadosSql(): string
    {
        $tabela    = $this->atendimentoModel->db->prefixTable('atendimentos');
        $condicoes = [];
        foreach (AtendimentoModel::LIMIAR_ATRASO_HORAS as $prioridade => $horas) {
            $condicoes[] = "({$tabela}.prioridade = '{$prioridade}' AND {$tabela}.data_hora < datetime('now', '-{$horas} hours'))";
        }

        return '(' . implode(' OR ', $condicoes) . ')';
    }

    /**
     * Enriquece a linha com rótulos, tempo de espera e flag de atraso.
     */
    private function complementarLinha(array $at, DateTime $agora): array
    {
        $at['prioridade_label'] = ConfiguracaoListaModel::rotuloCategoria('prioridade', $at['prioridade']);
        $at['situacao_label'] = ConfiguracaoListaModel::rotuloCategoria('status_atendimento', $at['situacao']);
        $at['origem_label'] = ConfiguracaoListaModel::rotuloCategoria('origem_atendimento', $at['origem']);
        $at['tempo_aguardando'] = '—';

        $inicio = DateTime::createFromFormat('Y-m-d H:i:s', (string) $at['data_hora']);
        if ($inicio !== false && $inicio <= $agora) {
            $at['tempo_aguardando'] = $this->tempoDecorrido($inicio, $agora);

            $limiar = AtendimentoModel::LIMIAR_ATRASO_HORAS[$at['prioridade']] ?? 72;
            $validade = clone $inicio;
            $validade->add(new DateInterval('PT' . $limiar . 'H'));
            $at['atrasado'] = $agora > $validade;
        } else {
            $at['atrasado'] = false;
        }

        return $at;
    }

    private function tempoDecorrido(DateTimeInterface $inicio, DateTimeInterface $agora): string
    {
        $diff = $agora->diff($inicio);

        if ($diff->d > 0) {
            return $diff->d . ' d ' . $diff->h . ' h';
        }
        if ($diff->h > 0) {
            return $diff->h . ' h ' . $diff->m . ' min';
        }

        return $diff->i . ' min';
    }
}