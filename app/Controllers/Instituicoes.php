<?php

namespace App\Controllers;

use App\Models\InstituicaoModel;
use App\Models\ServicoInstituicaoModel;

/**
 * Cadastro das instituições da Rede de Proteção (reutilizado pelos
 * Encaminhamentos na escolha do órgão/instituição de destino).
 */
class Instituicoes extends BaseController
{
    protected $instituicaoModel;

    public function __construct()
    {
        $this->instituicaoModel = new InstituicaoModel();
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
     * Listagem das instituições com filtros compactos (nome, tipo, município,
     * serviço e situação).
     */
    public function index()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'q'         => trim((string) $this->request->getGet('q')),
            'tipo'      => (string) $this->request->getGet('tipo'),
            'municipio' => trim((string) $this->request->getGet('municipio')),
            'servico'   => trim((string) $this->request->getGet('servico')),
            'situacao'  => (string) $this->request->getGet('situacao'),
        ];

        $model = $this->instituicaoModel;

        if ($filtros['q'] !== '') {
            $model->like('nome', $filtros['q']);
        }
        if ($filtros['tipo'] !== '' && array_key_exists($filtros['tipo'], InstituicaoModel::TIPOS)) {
            $model->where('tipo', $filtros['tipo']);
        }
        if ($filtros['municipio'] !== '') {
            $model->like('municipio', $filtros['municipio']);
        }
        if ($filtros['servico'] !== '') {
            $model->join('rede_servicos', 'rede_servicos.instituicao_id = rede_instituicoes.id', 'inner')
                ->like('rede_servicos.nome', $filtros['servico'])
                ->groupBy('rede_instituicoes.id');
        }
        if ($filtros['situacao'] !== '') {
            $model->where('ativo', $filtros['situacao'] === 'inativas' ? 0 : 1);
        }

        $instituicoes = $model->orderBy('nome', 'ASC')->paginate(15);

        $servicosPor = [];
        foreach ($instituicoes as $instituicao) {
            $servicosPor[(int) $instituicao['id']] = $this->instituicaoModel->servicos((int) $instituicao['id']);
        }

        $data = [
            'titulo'        => 'Instituições da Rede de Proteção',
            'instituicoes'  => $instituicoes,
            'servicosPor'   => $servicosPor,
            'pager'         => $this->instituicaoModel->pager,
            'filtros'       => $filtros,
            'tipos'         => InstituicaoModel::TIPOS,
        ];

        return view('rede/instituicoes/lista', $data);
    }

    /**
     * Formulário de nova instituição.
     */
    public function nova()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $data = [
            'titulo'        => 'Nova Instituição',
            'instituicao'   => null,
            'servicos'      => [],
            'tipos'         => InstituicaoModel::TIPOS,
        ];

        return view('rede/instituicoes/form', $data);
    }

    /**
     * Registra a instituição e os serviços oferecidos.
     */
    public function criar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $dados = $this->dadosDoPost();

        if (! $this->instituicaoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->instituicaoModel->errors());
        }

        $id = (int) $this->instituicaoModel->getInsertID();
        $this->instituicaoModel->substituirServicos($id, (array) $this->request->getPost('servicos'));

        return redirect()->to('/rede/instituicoes/' . $id)
            ->with('sucesso', 'Instituição cadastrada na rede de proteção.');
    }

    /**
     * Ficha da instituição, com serviços e área preparada para os
     * encaminhamentos enviados a ela (funcionalidade futura).
     */
    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $instituicao = $this->instituicaoModel->find($id);
        if ($instituicao === null) {
            return redirect()->to('/rede/instituicoes')->with('erro', 'Instituição não encontrada.');
        }

        $data = [
            'titulo'        => $instituicao['nome'],
            'instituicao'   => $instituicao,
            'servicos'      => $this->instituicaoModel->servicos($id),
            'tipos'         => InstituicaoModel::TIPOS,
        ];

        return view('rede/instituicoes/detalhes', $data);
    }

    /**
     * Formulário de edição no mesmo registro.
     */
    public function editar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $instituicao = $this->instituicaoModel->find($id);
        if ($instituicao === null) {
            return redirect()->to('/rede/instituicoes')->with('erro', 'Instituição não encontrada.');
        }

        $data = [
            'titulo'        => 'Editar Instituição',
            'instituicao'   => $instituicao,
            'servicos'      => $this->instituicaoModel->servicos($id),
            'tipos'         => InstituicaoModel::TIPOS,
        ];

        return view('rede/instituicoes/form', $data);
    }

    /**
     * Atualiza dados e substitui a lista de serviços.
     */
    public function atualizar(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $instituicao = $this->instituicaoModel->find($id);
        if ($instituicao === null) {
            return redirect()->to('/rede/instituicoes')->with('erro', 'Instituição não encontrada.');
        }

        $dados = $this->dadosDoPost();

        if (! $this->instituicaoModel->update($id, $dados)) {
            return redirect()->back()->withInput()->with('erros', $this->instituicaoModel->errors());
        }

        $this->instituicaoModel->substituirServicos($id, (array) $this->request->getPost('servicos'));

        return redirect()->to('/rede/instituicoes/' . $id)
            ->with('sucesso', 'Instituição atualizada.');
    }

    /**
     * Ativa/desativa a instituição (nunca exclui fisicamente).
     */
    public function mudarSituacao(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $instituicao = $this->instituicaoModel->find($id);
        if ($instituicao === null) {
            return redirect()->to('/rede/instituicoes')->with('erro', 'Instituição não encontrada.');
        }

        $novoAtivo = (int) $instituicao['ativo'] === 1 ? 0 : 1;
        $this->instituicaoModel->update($id, ['ativo' => $novoAtivo]);

        return redirect()->back()->with(
            'sucesso',
            $novoAtivo === 1 ? 'Instituição reativada.' : 'Instituição desativada (histórico preservado).'
        );
    }

    /**
     * Busca AJAX de instituições ATIVAS, usada no formulário de novo
     * encaminhamento. Instituições inativas não aparecem como opção.
     */
    public function buscar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $termo = trim((string) $this->request->getGet('term'));
        $model = $this->instituicaoModel->where('ativo', 1);

        if ($termo !== '') {
            $model->groupStart()
                ->like('nome', $termo)
                ->orLike('municipio', $termo)
                ->groupEnd();
        }

        $instituicoes = $model->orderBy('nome', 'ASC')->limit(15)->findAll();

        $resultado = array_map(static function ($instituicao) {
            return [
                'id'          => (int) $instituicao['id'],
                'nome'        => (string) $instituicao['nome'],
                'tipo'        => (string) $instituicao['tipo'],
                'tipo_rotulo' => InstituicaoModel::TIPOS[$instituicao['tipo']] ?? $instituicao['tipo'],
                'municipio'   => (string) ($instituicao['municipio'] ?? ''),
            ];
        }, $instituicoes);

        return $this->response->setJSON(['instituicoes' => $resultado]);
    }

    /**
     * Monta os campos do formulário a partir do POST.
     */
    private function dadosDoPost(): array
    {
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        return [
            'nome'                => $limpar($this->request->getPost('nome')),
            'tipo'                => (string) $this->request->getPost('tipo'),
            'endereco'            => $limpar($this->request->getPost('endereco')),
            'municipio'           => $limpar($this->request->getPost('municipio')),
            'telefone'            => $limpar($this->request->getPost('telefone')),
            'whatsapp'            => $limpar($this->request->getPost('whatsapp')),
            'email'               => $limpar($this->request->getPost('email')),
            'responsavel'         => $limpar($this->request->getPost('responsavel')),
            'horario_atendimento' => $limpar($this->request->getPost('horario_atendimento')),
            'observacoes'         => $limpar($this->request->getPost('observacoes')),
            'ativo'               => (int) $this->request->getPost('ativo') === 0 ? 0 : 1,
        ];
    }
}