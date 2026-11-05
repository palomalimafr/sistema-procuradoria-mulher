<?php

namespace App\Controllers;

use App\Libraries\ContextoMulher;
use App\Models\AtendimentoModel;
use App\Models\ConfiguracaoListaModel;
use App\Models\MedidaProtetivaModel;
use App\Models\MulherModel;
use App\Models\ProcessoHistoricoModel;
use App\Models\ProcessoModel;
use App\Models\UserModel;

class Processos extends BaseController
{
    protected $processoModel;

    public function __construct()
    {
        $this->processoModel = new ProcessoModel();
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
     * Listagem de processos com filtros compactos (mesmo padrão de Tarefas).
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'mulher'      => trim((string) $this->request->getGet('mulher')),
            'numero'      => trim((string) $this->request->getGet('numero')),
            'tipo'        => (string) $this->request->getGet('tipo'),
            'orgao'       => (string) $this->request->getGet('orgao'),
            'vara'        => trim((string) $this->request->getGet('vara')),
            'comarca'     => trim((string) $this->request->getGet('comarca')),
            'situacao'    => (string) $this->request->getGet('situacao'),
            'responsavel' => max(0, (int) $this->request->getGet('responsavel')),
            'data_de'     => trim((string) $this->request->getGet('data_de')),
            'data_ate'    => trim((string) $this->request->getGet('data_ate')),
        ];

        $model = $this->processoModel->comDados();

        if ($filtros['mulher'] !== '') {
            $termo = $filtros['mulher'];
            $model->groupStart()
                ->like('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }
        if ($filtros['numero'] !== '') {
            $model->like('processos.numero_processo', $filtros['numero']);
        }
        if ($filtros['tipo'] !== '' && array_key_exists($filtros['tipo'], ProcessoModel::TIPOS)) {
            $model->where('processos.tipo', $filtros['tipo']);
        }
        if ($filtros['orgao'] !== '' && array_key_exists($filtros['orgao'], ProcessoModel::orgaosComChaves())) {
            $model->where('processos.orgao', $filtros['orgao']);
        }
        if ($filtros['vara'] !== '') {
            $model->like('processos.vara', $filtros['vara']);
        }
        if ($filtros['comarca'] !== '') {
            $model->like('processos.comarca', $filtros['comarca']);
        }
        if ($filtros['situacao'] !== '' && array_key_exists($filtros['situacao'], ConfiguracaoListaModel::todasCategoria('situacao_processo'))) {
            $model->where('processos.situacao', $filtros['situacao']);
        }
        if ($filtros['responsavel'] > 0) {
            $model->where('processos.responsavel_id', $filtros['responsavel']);
        }
        if ($this->dataValida($filtros['data_de'])) {
            $model->where('processos.data >=', $filtros['data_de']);
        }
        if ($this->dataValida($filtros['data_ate'])) {
            $model->where('processos.data <=', $filtros['data_ate']);
        }

        $processos = $model->orderBy('processos.data', 'DESC')
            ->orderBy('processos.id', 'DESC')
            ->paginate(15);

        return view('processos/lista', [
            'titulo'    => 'Processos',
            'processos' => $processos,
            'pager'     => $this->processoModel->pager,
            'filtros'   => $filtros,
            'tipos'     => ProcessoModel::TIPOS,
            'situacoes' => ConfiguracaoListaModel::todasCategoria('situacao_processo'),
            'orgaos'    => ProcessoModel::orgaosComChaves(),
            'equipe'    => $this->equipeAtiva(),
        ]);
    }

    /**
     * Formulário de novo processo, com vínculos opcionais pré-selecionados
     * (mulher, atendimento e medida protetiva).
     */
    public function nova()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $mulherPreselecionada = null;
        $atendimentoPreselecionado = null;
        $medidaPreselecionada = null;
        $mulherContexto = 0;

        $atendimentoId = (int) $this->request->getGet('atendimento_id');
        if ($atendimentoId > 0) {
            $a = (new AtendimentoModel())->comDados()
                ->where('atendimentos.id', $atendimentoId)
                ->first();
            if ($a !== null) {
                $atendimentoPreselecionado = $this->linhaAtendimento($a);
                $mulherPreselecionada = $this->mulherPreselecionada((int) $a['mulher_id']);
                $mulherContexto = (int) $a['mulher_id'];
            }
        }

        $medidaId = (int) $this->request->getGet('medida_protetiva_id');
        if ($medidaId > 0) {
            $m = (new MedidaProtetivaModel())->comDados()
                ->where('medidas_protetivas.id', $medidaId)
                ->first();
            if ($m !== null) {
                $medidaPreselecionada = $this->linhaMedida($m);
                if ($mulherPreselecionada === null) {
                    $mulherPreselecionada = $this->mulherPreselecionada((int) $m['mulher_id']);
                    $mulherContexto = (int) $m['mulher_id'];
                }
            }
        }

        $mulherId = (int) $this->request->getGet('mulher_id');
        if ($mulherId > 0 && $mulherPreselecionada === null) {
            $mulherPreselecionada = $this->mulherPreselecionada($mulherId);
            $mulherContexto = $mulherId;
        }

        return view('processos/form', [
            'titulo'                   => 'Novo Processo',
            'editando'                 => false,
            'processo'                 => null,
            'mulherPreselecionada'     => $mulherPreselecionada,
            'atendimentoPreselecionado' => $atendimentoPreselecionado,
            'medidaPreselecionada'     => $medidaPreselecionada,
            'contexto'                 => (new ContextoMulher())->montar($mulherContexto),
            'usuarioId'                => (int) session()->get('usuario_id'),
            'usuarioNome'              => (string) session()->get('usuario_nome'),
            'equipe'                   => $this->equipeAtiva(),
            'tipos'                    => ProcessoModel::TIPOS,
            'situacoes'                => ConfiguracaoListaModel::opcoesCategoria('situacao_processo'),
            'orgaos'                   => ProcessoModel::orgaosComChaves(),
        ]);
    }

    /**
     * Registra o processo. A mulher é sempre obrigatória; o atendimento e a
     * medida protetiva são opcionais (quando aplicável).
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        [$mulherId, $atendimentoId, $medidaId, $erro] = $this->validarVinculos();
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        $dados = $this->dadosDoPost();
        $dados['mulher_id'] = $mulherId;
        $dados['atendimento_id'] = $atendimentoId;
        $dados['medida_protetiva_id'] = $medidaId;

        if (! $this->processoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->processoModel->errors());
        }

        $id = (int) $this->processoModel->getInsertID();

        (new ProcessoHistoricoModel())->registrar(
            $id,
            'criacao',
            null,
            $dados['situacao'],
            (int) session()->get('usuario_id'),
            'Processo registrado.'
        );

        $this->auditar('CREATE', 'processos', $id, 'processo', null, [
            'mulher_id' => $mulherId,
            'situacao'  => (string) ($dados['situacao'] ?? ''),
        ]);

        return redirect()->to('/processos/' . $id)
            ->with('sucesso', 'Processo registrado.');
    }

    /**
     * Ficha do processo: dados, relacionamentos e histórico.
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $processo = $this->processoModel->comDados()
            ->where('processos.id', $id)
            ->first();

        if ($processo === null) {
            return redirect()->to('/processos')->with('erro', 'Processo não encontrado.');
        }

        return view('processos/detalhes', [
            'titulo'          => 'Processo #' . $id,
            'processo'        => $processo,
            'tipos'           => ProcessoModel::TIPOS,
            'situacoes'       => ConfiguracaoListaModel::todasCategoria('situacao_processo'),
            'situacoesMedida' => ConfiguracaoListaModel::todasCategoria('situacao_medida'),
            'orgaos'          => ProcessoModel::orgaosComChaves(),
            'historico'       => (new ProcessoHistoricoModel())->listarPorProcesso($id),
        ]);
    }

    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $processo = $this->processoModel->comDados()
            ->where('processos.id', $id)
            ->first();

        if ($processo === null) {
            return redirect()->to('/processos')->with('erro', 'Processo não encontrado.');
        }

        return view('processos/form', [
            'titulo'                   => 'Editar Processo #' . $id,
            'editando'                 => true,
            'processo'                 => $processo,
            'mulherPreselecionada'     => $this->mulherPreselecionada((int) $processo['mulher_id']),
            'atendimentoPreselecionado' => ! empty($processo['atendimento_id'])
                ? $this->linhaAtendimento($processo)
                : null,
            'medidaPreselecionada'     => ! empty($processo['medida_protetiva_id'])
                ? $this->linhaMedida($processo)
                : null,
            'usuarioId'                => (int) session()->get('usuario_id'),
            'usuarioNome'              => (string) session()->get('usuario_nome'),
            'equipe'                   => $this->equipeAtiva(),
            'tipos'                    => ProcessoModel::TIPOS,
            'situacoes'                => ConfiguracaoListaModel::opcoesCategoria('situacao_processo'),
            'orgaos'                   => ProcessoModel::orgaosComChaves(),
        ]);
    }

    /**
     * Atualiza o MESMO registro (não cria novo processo).
     */
    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $atual = $this->processoModel->find($id);
        if ($atual === null) {
            return redirect()->to('/processos')->with('erro', 'Processo não encontrado.');
        }

        [$mulherId, $atendimentoId, $medidaId, $erro] = $this->validarVinculos();
        if ($erro !== null) {
            $campo = (string) key($erro);
            $mensagem = (string) reset($erro);

            return redirect()->back()->withInput()->with('erros', [$campo => $mensagem]);
        }

        $dados = $this->dadosDoPost();
        $dados['mulher_id'] = $mulherId;
        $dados['atendimento_id'] = $atendimentoId;
        $dados['medida_protetiva_id'] = $medidaId;

        if (! $this->processoModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('erros', $this->processoModel->errors());
        }

        $historico = new ProcessoHistoricoModel();
        $usuarioId = (int) session()->get('usuario_id');

        $situacaoAnterior = $atual['situacao'] === null ? null : (string) $atual['situacao'];
        if ($situacaoAnterior !== $dados['situacao']) {
            $historico->registrar($id, 'situacao', $situacaoAnterior, $dados['situacao'], $usuarioId);
        }

        $responsavelAnterior = $atual['responsavel_id'] === null ? null : (int) $atual['responsavel_id'];
        if ($responsavelAnterior !== $dados['responsavel_id']) {
            $historico->registrar(
                $id,
                'responsavel',
                null,
                null,
                $usuarioId,
                'Responsável alterado no registro do processo.'
            );
        }

        $this->auditar('UPDATE', 'processos', $id, 'processo', null, []);

        return redirect()->to('/processos/' . $id)
            ->with('sucesso', 'Processo atualizado.');
    }

    /**
     * Localiza medidas protetivas existentes (AJAX) para o vínculo opcional.
     * Opcionalmente restringe à mulher já selecionada no formulário.
     */
    public function buscarMedidas()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo = trim((string) $this->request->getGet('term'));
        $mulherId = (int) $this->request->getGet('mulher_id');

        $model = (new MedidaProtetivaModel())->comDados()
            ->where('medidas_protetivas.possui_medida', 1);

        if ($mulherId > 0) {
            $model->where('medidas_protetivas.mulher_id', $mulherId);
        }

        if ($termo !== '') {
            $model->groupStart()
                ->like('medidas_protetivas.numero', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->orLike('dados_mulher.cpf', $termo)
                ->groupEnd();
        }

        $medidas = $model->orderBy('medidas_protetivas.data_solicitacao', 'DESC')
            ->limit(15)
            ->findAll();

        $resultado = array_map(
            fn (array $m): array => $this->linhaMedida($m),
            $medidas
        );

        return $this->response
            ->setContentType('application/json')
            ->setJSON(['medidas' => $resultado]);
    }

    /**
     * Valida mulher (obrigatória) e vínculos opcionais (atendimento e medida
     * protetiva, ambos pertencentes à mesma mulher).
     *
     * @return array{0:int, 1:int|null, 2:int|null, 3:array|null}
     */
    private function validarVinculos(): array
    {
        $mulherId = (int) $this->request->getPost('mulher_id');
        if ($mulherId <= 0) {
            return [0, null, null, ['mulher_id' => 'Localize e selecione a mulher vinculada.']];
        }

        if (! (new MulherModel())->find($mulherId)) {
            return [0, null, null, ['mulher_id' => 'A mulher selecionada não existe mais.']];
        }

        $atendimentoId = (int) $this->request->getPost('atendimento_id');
        if ($atendimentoId > 0) {
            $atendimento = (new AtendimentoModel())->find($atendimentoId);
            if ($atendimento === null) {
                return [$mulherId, null, null, ['atendimento_id' => 'O atendimento selecionado não existe mais.']];
            }
            if ((int) $atendimento['mulher_id'] !== $mulherId) {
                return [$mulherId, null, null, ['atendimento_id' => 'O atendimento selecionado pertence a outra mulher.']];
            }
        }

        $medidaId = (int) $this->request->getPost('medida_protetiva_id');
        if ($medidaId > 0) {
            $medida = (new MedidaProtetivaModel())->find($medidaId);
            if ($medida === null) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, null, ['medida_protetiva_id' => 'A medida protetiva selecionada não existe mais.']];
            }
            if ((int) $medida['mulher_id'] !== $mulherId) {
                return [$mulherId, $atendimentoId > 0 ? $atendimentoId : null, null, ['medida_protetiva_id' => 'A medida protetiva selecionada pertence a outra mulher.']];
            }
        }

        return [
            $mulherId,
            $atendimentoId > 0 ? $atendimentoId : null,
            $medidaId > 0 ? $medidaId : null,
            null,
        ];
    }

    /**
     * Monta os campos editáveis a partir do POST.
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $tipo = (string) $this->request->getPost('tipo');
        if (! array_key_exists($tipo, ProcessoModel::TIPOS)) {
            $tipo = 'outros';
        }

        $situacao = (string) $this->request->getPost('situacao');
        if (! array_key_exists($situacao, ConfiguracaoListaModel::todasCategoria('situacao_processo'))) {
            $situacao = 'em_andamento';
        }

        $orgao = (string) $this->request->getPost('orgao');
        if (! array_key_exists($orgao, ProcessoModel::orgaosComChaves())) {
            $orgao = '';
        }

        $responsavelId = (int) $this->request->getPost('responsavel_id');
        if ($responsavelId <= 0) {
            $responsavelId = (int) session()->get('usuario_id');
        }

        return [
            'numero_processo' => $limpar($this->request->getPost('numero_processo')),
            'tipo'            => $tipo,
            'orgao'           => $orgao === '' ? null : $orgao,
            'vara'            => $limpar($this->request->getPost('vara')),
            'comarca'         => $limpar($this->request->getPost('comarca')),
            'data'            => $limpar($this->request->getPost('data')),
            'situacao'        => $situacao,
            'responsavel_id'  => $responsavelId > 0 ? $responsavelId : null,
            'observacoes'     => $limpar($this->request->getPost('observacoes')),
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

    private function linhaAtendimento(array $a): array
    {
        return [
            'id'               => (int) ($a['atendimento_id'] ?? $a['id'] ?? 0),
            'protocolo'        => (string) ($a['atendimento_protocolo'] ?? $a['protocolo'] ?? ''),
            'data_hora'        => (string) ($a['data_hora'] ?? $a['atendimento_data_hora'] ?? ''),
            'mulher_id'        => (int) ($a['mulher_id'] ?? 0),
            'mulher_nome'      => (string) ($a['mulher_nome'] ?? ''),
            'data_hora_formatted' => ! empty($a['data_hora'] ?? $a['atendimento_data_hora'] ?? '')
                ? date('d/m/Y H:i', strtotime((string) ($a['data_hora'] ?? $a['atendimento_data_hora'])))
                : '',
        ];
    }

    private function linhaMedida(array $m): array
    {
        $id = (int) ($m['medida_protetiva_id'] ?? $m['id'] ?? 0);
        $numero = (string) ($m['medida_numero'] ?? $m['numero'] ?? '');

        return [
            'id'               => $id,
            'numero'           => $numero,
            'titulo'           => '#' . $id . ($numero !== '' ? ' · ' . $numero : ''),
            'situacao'         => (string) ($m['medida_situacao'] ?? $m['situacao'] ?? ''),
            'data_solicitacao' => (string) ($m['data_solicitacao'] ?? ''),
            'mulher_id'        => (int) ($m['mulher_id'] ?? 0),
            'mulher_nome'      => (string) ($m['mulher_nome'] ?? ''),
        ];
    }
}