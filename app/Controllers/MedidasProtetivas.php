<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\MedidaDescumprimentoModel;
use App\Models\MedidaHistoricoModel;
use App\Models\MedidaProtetivaModel;
use App\Models\MulherModel;

class MedidasProtetivas extends BaseController
{
    protected $medidaModel;

    public function __construct()
    {
        $this->medidaModel = new MedidaProtetivaModel();
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

    /**
     * Listagem de medidas protetivas com filtros compactos (painel recolhível,
     * mesmo padrão do módulo de Tarefas).
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'q'              => trim((string) $this->request->getGet('q')),
            'situacao'       => (string) $this->request->getGet('situacao'),
            'orgao'          => (string) $this->request->getGet('orgao'),
            'sol_de'         => trim((string) $this->request->getGet('sol_de')),
            'sol_ate'        => trim((string) $this->request->getGet('sol_ate')),
            'con_de'         => trim((string) $this->request->getGet('con_de')),
            'con_ate'        => trim((string) $this->request->getGet('con_ate')),
            'val_de'         => trim((string) $this->request->getGet('val_de')),
            'val_ate'        => trim((string) $this->request->getGet('val_ate')),
            'descumprimento' => (string) $this->request->getGet('descumprimento'),
        ];

        $model = $this->medidaModel->comDados();

        if ($filtros['q'] !== '') {
            $termo = $filtros['q'];
            $model->groupStart()
                ->like('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->orLike('medidas_protetivas.numero', $termo)
                ->orLike('medidas_protetivas.processo', $termo)
                ->groupEnd();
        }
        if ($filtros['situacao'] !== '' && array_key_exists($filtros['situacao'], ConfiguracaoListaModel::todasCategoria('situacao_medida'))) {
            $model->where('medidas_protetivas.situacao', $filtros['situacao']);
        }
        if ($filtros['orgao'] !== '' && array_key_exists($filtros['orgao'], MedidaProtetivaModel::orgaosComChaves())) {
            $model->where('medidas_protetivas.orgao', $filtros['orgao']);
        }
        if ($this->dataValida($filtros['sol_de'])) {
            $model->where('medidas_protetivas.data_solicitacao >=', $filtros['sol_de']);
        }
        if ($this->dataValida($filtros['sol_ate'])) {
            $model->where('medidas_protetivas.data_solicitacao <=', $filtros['sol_ate']);
        }
        if ($this->dataValida($filtros['con_de'])) {
            $model->where('medidas_protetivas.data_concessao >=', $filtros['con_de']);
        }
        if ($this->dataValida($filtros['con_ate'])) {
            $model->where('medidas_protetivas.data_concessao <=', $filtros['con_ate']);
        }
        if ($this->dataValida($filtros['val_de'])) {
            $model->where('medidas_protetivas.data_validade >=', $filtros['val_de']);
        }
        if ($this->dataValida($filtros['val_ate'])) {
            $model->where('medidas_protetivas.data_validade <=', $filtros['val_ate']);
        }
        if ($filtros['descumprimento'] === 'com') {
            $model->where('medidas_protetivas.id IN (SELECT medida_id FROM medida_descumprimentos)', null, false);
        } elseif ($filtros['descumprimento'] === 'sem') {
            $model->where('medidas_protetivas.id NOT IN (SELECT medida_id FROM medida_descumprimentos)', null, false);
        }

        $medidas = $model->orderBy('medidas_protetivas.data_solicitacao', 'DESC')
            ->orderBy('medidas_protetivas.id', 'DESC')
            ->paginate(15);

        $data = [
            'titulo'  => 'Medidas Protetivas',
            'medidas' => $medidas,
            'pager'   => $this->medidaModel->pager,
            'filtros' => $filtros,
            'situacoes' => ConfiguracaoListaModel::todasCategoria('situacao_medida'),
            'orgaos' => MedidaProtetivaModel::orgaosComChaves(),
            'hoje'   => date('Y-m-d'),
        ];

        return view('medidas_protetivas/lista', $data);
    }

    /**
     * Formulário de nova medida protetiva, com vínculos opcionais
     * pré-selecionados (mulher e atendimento que originou a solicitação).
     *
     * Quando chega apenas ?mulher_id= (a partir da ficha), o atendimento
     * só é pré-selecionado se existir EXATAMENTE UM em aberto. Havendo
     * zero ou mais de um, nada é pré-selecionado e a profissional escolhe
     * — a decisão nunca é inferida pelo sistema.
     */
    public function nova()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherPreselecionada = null;
        $atendimentoPreselecionado = null;
        $mulherContexto = 0;

        $atendimentoId = (int) $this->request->getGet('atendimento_id');
        if ($atendimentoId > 0) {
            $a = (new AtendimentoModel())->comDados()
                ->where('atendimentos.id', $atendimentoId)
                ->first();
            if ($a !== null) {
                $atendimentoPreselecionado = AtendimentoModel::linhaResumo($a);
                $mulherPreselecionada = $this->mulherPreselecionada((int) $a['mulher_id']);
                $mulherContexto = (int) $a['mulher_id'];
            }
        }

        $mulherId = (int) $this->request->getGet('mulher_id');
        if ($mulherId > 0 && $mulherPreselecionada === null) {
            $mulherPreselecionada = $this->mulherPreselecionada($mulherId);
            $mulherContexto = $mulherId;
            $unico = (new AtendimentoModel())->unicoAbertoPorMulher($mulherId);
            if ($unico !== null) {
                $atendimentoPreselecionado = AtendimentoModel::linhaResumo($unico);
            }
        }

        return view('medidas_protetivas/form', [
            'titulo'                     => 'Nova Medida Protetiva',
            'editando'                   => false,
            'medida'                     => null,
            'mulherPreselecionada'       => $mulherPreselecionada,
            'atendimentoPreselecionado'  => $atendimentoPreselecionado,
            'contexto'                   => (new ContextoMulher())->montar($mulherContexto),
            'situacoes'                  => ConfiguracaoListaModel::opcoesCategoria('situacao_medida'),
            'orgaos'                     => MedidaProtetivaModel::orgaosComChaves(),
            'hoje'                       => date('Y-m-d'),
        ]);
    }

    /**
     * Registra a medida. A mulher é sempre obrigatória e o atendimento
     * também: a medida protetiva é sempre vinculada a um atendimento já
     * registrado da titular.
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        [$mulherId, $atendimentoId, $erro] = $this->validarVinculos(true);
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        $dados = $this->dadosDoPost();
        $dados['mulher_id'] = $mulherId;
        $dados['atendimento_id'] = $atendimentoId;

        if (! $this->medidaModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->medidaModel->errors());
        }

        $id = (int) $this->medidaModel->getInsertID();

        (new MedidaHistoricoModel())->registrar(
            $id,
            'criacao',
            null,
            ! empty($dados['situacao']) ? $dados['situacao'] : null,
            (int) session()->get('usuario_id'),
            ! empty($dados['possui_medida'])
                ? 'Medida protetiva registrada.'
                : 'Registrado que a mulher não possui medida protetiva.'
        );

        $this->auditar('CREATE', 'medidas', $id, 'medida', null, [
            'mulher_id'     => $mulherId,
            'possui_medida' => ! empty($dados['possui_medida']) ? 1 : 0,
            'situacao'      => ! empty($dados['situacao']) ? (string) $dados['situacao'] : null,
        ]);

        return redirect()->to('/medidas-protetivas/' . $id)
            ->with('sucesso', 'Medida protetiva registrada.');
    }

    /**
     * Ficha da medida: dados, descumprimentos e histórico.
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $medida = $this->medidaModel->comDados()
            ->where('medidas_protetivas.id', $id)
            ->first();

        if ($medida === null) {
            return redirect()->to('/medidas-protetivas')->with('erro', 'Medida protetiva não encontrada.');
        }

        return view('medidas_protetivas/detalhes', [
            'titulo'          => 'Medida Protetiva #' . $id,
            'medida'          => $medida,
            'situacoes'       => ConfiguracaoListaModel::todasCategoria('situacao_medida'),
            'fluxoSituacoes'  => MedidaProtetivaModel::FLUXO_SITUACOES,
            'orgaos'          => MedidaProtetivaModel::orgaosComChaves(),
            'descumprimentos' => (new MedidaDescumprimentoModel())->listarPorMedida($id),
            'historico'       => (new MedidaHistoricoModel())->listarPorMedida($id),
        ]);
    }

    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $medida = $this->medidaModel->comDados()
            ->where('medidas_protetivas.id', $id)
            ->first();

        if ($medida === null) {
            return redirect()->to('/medidas-protetivas')->with('erro', 'Medida protetiva não encontrada.');
        }

        return view('medidas_protetivas/form', [
            'titulo'                     => 'Editar Medida Protetiva #' . $id,
            'editando'                   => true,
            'medida'                     => $medida,
            'mulherPreselecionada'       => $this->mulherPreselecionada((int) $medida['mulher_id']),
            'atendimentoPreselecionado'  => ! empty($medida['atendimento_id'])
                ? AtendimentoModel::linhaResumo($medida)
                : null,
            'situacoes'                  => ConfiguracaoListaModel::opcoesCategoria('situacao_medida'),
            'orgaos'                     => MedidaProtetivaModel::orgaosComChaves(),
            'hoje'                       => date('Y-m-d'),
        ]);
    }

    /**
     * Atualiza a medida.
     *
     * O atendimento continua OPCIONAL na edição: as medidas registradas
     * antes desta regra podem não ter atendimento vinculado, e exigir um
     * agora quebraria a edição de registros legados já existentes.
     */
    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->medidaModel->find($id);
        if ($atual === null) {
            return redirect()->to('/medidas-protetivas')->with('erro', 'Medida protetiva não encontrada.');
        }

        [$mulherId, $atendimentoId, $erro] = $this->validarVinculos();
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        $dados = $this->dadosDoPost();
        $dados['mulher_id'] = $mulherId;
        $dados['atendimento_id'] = $atendimentoId;

        if (! $this->medidaModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('erros', $this->medidaModel->errors());
        }

        $situacaoAnterior = $atual['situacao'] === null ? null : (string) $atual['situacao'];
        if ($situacaoAnterior !== $dados['situacao']) {
            (new MedidaHistoricoModel())->registrar(
                $id,
                'situacao',
                $situacaoAnterior,
                $dados['situacao'],
                (int) session()->get('usuario_id')
            );
        }

        $this->auditar('UPDATE', 'medidas', $id, 'medida', null, []);

        return redirect()->to('/medidas-protetivas/' . $id)
            ->with('sucesso', 'Medida protetiva atualizada.');
    }

    /**
     * Atualização de situação conforme o fluxo (mesmo registro, nunca apaga).
     */
    public function mudarSituacao(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->medidaModel->find($id);
        if ($atual === null) {
            return redirect()->to('/medidas-protetivas')->with('erro', 'Medida protetiva não encontrada.');
        }

        if ((int) $atual['possui_medida'] !== 1) {
            return redirect()->back()->with('erro', 'Não é possível atualizar a situação quando não há medida registrada.');
        }

        $novoSituacao = (string) $this->request->getPost('situacao');
        if (! array_key_exists($novoSituacao, ConfiguracaoListaModel::todasCategoria('situacao_medida'))) {
            return redirect()->back()->with('erro', 'Situação inválida para a medida protetiva.');
        }

        $permitidos = MedidaProtetivaModel::FLUXO_SITUACOES[$atual['situacao']] ?? [];
        if (! in_array($novoSituacao, $permitidos, true)) {
            return redirect()->back()->with('erro', 'Transição de situação não permitida para esta medida.');
        }

        $this->medidaModel->update($id, ['situacao' => $novoSituacao]);

        (new MedidaHistoricoModel())->registrar(
            $id,
            'situacao',
            $atual['situacao'] === null ? null : (string) $atual['situacao'],
            $novoSituacao,
            (int) session()->get('usuario_id')
        );

        $this->auditar('STATUS_CHANGE', 'medidas', $id, 'medida', null, [
            'situacao_de'   => (string) ($atual['situacao'] ?? ''),
            'situacao_para' => $novoSituacao,
        ]);

        return redirect()->to('/medidas-protetivas/' . $id)
            ->with('sucesso', 'Situação da medida protetiva atualizada.');
    }

    /**
     * Formulário para registrar uma ocorrência de descumprimento.
     */
    public function novoDescumprimento(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $medida = $this->medidaModel->comDados()
            ->where('medidas_protetivas.id', $id)
            ->first();

        if ($medida === null) {
            return redirect()->to('/medidas-protetivas')->with('erro', 'Medida protetiva não encontrada.');
        }

        if ((int) $medida['possui_medida'] !== 1) {
            return redirect()->to('/medidas-protetivas/' . $id)
                ->with('erro', 'Não é possível registrar descumprimento quando não há medida registrada.');
        }

        return view('medidas_protetivas/descumprimento', [
            'titulo'  => 'Registrar Descumprimento',
            'medida'  => $medida,
            'situacoes' => ConfiguracaoListaModel::todasCategoria('situacao_medida'),
            'orgaos'  => MedidaProtetivaModel::orgaosComChaves(),
            'hoje'    => date('Y-m-d'),
        ]);
    }

    /**
     * Registra a ocorrência vinculada à medida. Nunca apaga ocorrências
     * anteriores.
     */
    public function registrarDescumprimento(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $medida = $this->medidaModel->find($id);
        if ($medida === null) {
            return redirect()->to('/medidas-protetivas')->with('erro', 'Medida protetiva não encontrada.');
        }

        if ((int) $medida['possui_medida'] !== 1) {
            return redirect()->back()->with('erro', 'Não é possível registrar descumprimento quando não há medida registrada.');
        }

        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $dados = [
            'medida_id'       => $id,
            'data_ocorrencia' => $limpar($this->request->getPost('data_ocorrencia')),
            'descricao'       => $limpar($this->request->getPost('descricao')),
            'observacoes'     => $limpar($this->request->getPost('observacoes')),
            'historico'       => $limpar($this->request->getPost('historico')),
            'usuario_id'      => (int) session()->get('usuario_id') > 0 ? (int) session()->get('usuario_id') : null,
        ];

        if (! (new MedidaDescumprimentoModel())->save($dados)) {
            return redirect()->back()->withInput()->with('erros', (new MedidaDescumprimentoModel())->errors());
        }

        (new MedidaHistoricoModel())->registrar(
            $id,
            'descumprimento',
            null,
            null,
            (int) session()->get('usuario_id'),
            $dados['descricao']
        );

        $this->auditar('UPDATE', 'medidas', $id, 'medida', null, [
            'descumprimento' => $dados['data_ocorrencia'] ?? null,
        ]);

        return redirect()->to('/medidas-protetivas/' . $id)
            ->with('sucesso', 'Descumprimento registrado.');
    }

    /**
     * Valida mulher (obrigatória) e atendimento (pertencente à mesma mulher).
     *
     * @param bool $atendimentoObrigatorio Na criação a medida exige
     *                                        atendimento; na edição o
     *                                        vínculo permanece opcional
     *                                        para preservar registros
     *                                        legados sem atendimento.
     *
     * @return array{0:int, 1:int|null, 2:array|null}
     */
    private function validarVinculos(bool $atendimentoObrigatorio = false): array
    {
        $mulherId = (int) $this->request->getPost('mulher_id');
        if ($mulherId <= 0) {
            return [0, null, ['mulher_id' => 'Localize e selecione a mulher vinculada.']];
        }

        if (! (new MulherModel())->find($mulherId)) {
            return [0, null, ['mulher_id' => 'A mulher selecionada não existe mais.']];
        }

        $atendimentoId = (int) $this->request->getPost('atendimento_id');
        if ($atendimentoId <= 0) {
            return $atendimentoObrigatorio
                ? [$mulherId, null, ['atendimento_id' => 'Selecione o atendimento que originou a solicitação.']]
                : [$mulherId, null, null];
        }

        $atendimento = (new AtendimentoModel())->find($atendimentoId);
        if ($atendimento === null) {
            return [$mulherId, null, ['atendimento_id' => 'O atendimento selecionado não existe mais.']];
        }

        if ((int) $atendimento['mulher_id'] !== $mulherId) {
            return [$mulherId, null, ['atendimento_id' => 'O atendimento selecionado pertence a outra mulher.']];
        }

        return [$mulherId, $atendimentoId, null];
    }

    /**
     * Monta os campos editáveis a partir do POST. Quando a resposta de
     * "Possui medida protetiva?" for negativa, os campos específicos da medida
     * não são exigidos e ficam vazios.
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $possuiMedida = (int) $this->request->getPost('possui_medida') === 1 ? 1 : 0;

        $situacao = (string) $this->request->getPost('situacao');
        if (! array_key_exists($situacao, ConfiguracaoListaModel::todasCategoria('situacao_medida'))) {
            $situacao = '';
        }

        if ($possuiMedida === 0) {
            return [
                'possui_medida'    => 0,
                'numero'           => null,
                'data_solicitacao' => null,
                'data_concessao'   => null,
                'orgao'            => null,
                'processo'         => null,
                'situacao'         => null,
                'data_validade'    => null,
                'observacoes'      => $limpar($this->request->getPost('observacoes')),
            ];
        }

        return [
            'possui_medida'    => 1,
            'numero'           => $limpar($this->request->getPost('numero')),
            'data_solicitacao' => $limpar($this->request->getPost('data_solicitacao')),
            'data_concessao'   => $limpar($this->request->getPost('data_concessao')),
            'orgao'            => $limpar($this->request->getPost('orgao')),
            'processo'         => $limpar($this->request->getPost('processo')),
            'situacao'         => $situacao === '' ? 'solicitada' : $situacao,
            'data_validade'    => $limpar($this->request->getPost('data_validade')),
            'observacoes'      => $limpar($this->request->getPost('observacoes')),
        ];
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