<?php

namespace App\Controllers;

use App\Models\AcolhimentoModel;
use App\Models\AvaliacaoRiscoModel;
use App\Models\UserModel;

class Avaliacoes extends BaseController
{
    protected $avaliacaoModel;

    public function __construct()
    {
        $this->avaliacaoModel = new AvaliacaoRiscoModel();
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
     * Consulta das avaliações de risco registradas.
     */
    public function consulta()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $filtros = [
            'q'               => trim((string) $this->request->getGet('q')),
            'classificacao'   => (string) $this->request->getGet('classificacao'),
            'profissional_id' => max(0, (int) $this->request->getGet('profissional')),
            'data_de'         => (string) $this->request->getGet('data_de'),
            'data_ate'        => (string) $this->request->getGet('data_ate'),
        ];

        $model = $this->avaliacaoModel->comDados();

        if ($filtros['q'] !== '') {
            $termo = $filtros['q'];
            $model->groupStart()
                ->like('atendimentos.protocolo', $termo)
                ->orLike('dados_mulher.nome_completo', $termo)
                ->orLike('dados_mulher.nome_social', $termo)
                ->groupEnd();
        }
        if ($filtros['classificacao'] !== '' && array_key_exists($filtros['classificacao'], AcolhimentoModel::RISCOS)) {
            $model->where('avaliacoes_risco.classificacao', $filtros['classificacao']);
        }
        if ($filtros['profissional_id'] > 0) {
            $model->where('avaliacoes_risco.profissional_id', $filtros['profissional_id']);
        }
        if ($filtros['data_de'] !== '') {
            $model->where('avaliacoes_risco.data_hora >=', $filtros['data_de'] . ' 00:00:00');
        }
        if ($filtros['data_ate'] !== '') {
            $model->where('avaliacoes_risco.data_hora <=', $filtros['data_ate'] . ' 23:59:59');
        }

        $avaliacoes = $model->orderBy('avaliacoes_risco.data_hora', 'DESC')->paginate(10);

        $data = [
            'titulo'      => 'Avaliações de risco',
            'avaliacoes'  => $avaliacoes,
            'pager'       => $this->avaliacaoModel->pager,
            'filtros'     => $filtros,
            'equipe'      => $this->equipeAtiva(),
            'riscos'      => AcolhimentoModel::RISCOS,
        ];

        return view('avaliacoes/lista', $data);
    }

    /**
     * Formulário de avaliação de risco para um acolhimento (sempre vinculado).
     */
    public function novo(int $acolhimentoId)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $acolhimento = (new \App\Models\AcolhimentoModel())->comDados()
            ->where('acolhimentos.id', $acolhimentoId)
            ->first();

        if ($acolhimento === null) {
            return redirect()->to('/acolhimentos')->with('erro', 'Acolhimento não encontrado.');
        }

        $data = [
            'titulo'       => 'Avaliação de risco',
            'acolhimento'  => $acolhimento,
            'perguntas'    => $this->avaliacaoModel->perguntas(),
            'riscos'       => AcolhimentoModel::RISCOS,
            'usuarioNome'  => (string) session()->get('usuario_nome'),
            'sugestao'     => '',
        ];

        return view('avaliacoes/form', $data);
    }

    /**
     * Salva a avaliação. A classificação é sempre do profissional;
     * a sugestão automática é apenas um auxílio, gravada para transparência.
     */
    public function salvar()
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $acolhimentoId = (int) $this->request->getPost('acolhimento_id');

        if ($acolhimentoId <= 0 || (new \App\Models\AcolhimentoModel())->find($acolhimentoId) === null) {
            return redirect()->back()->withInput()->with('erros', ['acolhimento_id' => 'Selecione o acolhimento vinculado.']);
        }

        $classificacao = (string) $this->request->getPost('classificacao');
        if (! array_key_exists($classificacao, AcolhimentoModel::RISCOS)) {
            return redirect()->back()->withInput()->with('erros', ['classificacao' => 'Informe a classificação de risco.']);
        }

        $bool = static fn ($valor) => ! empty($valor) ? 1 : 0;
        $limpar = static fn ($valor) => $valor !== null && trim((string) $valor) !== '' ? trim((string) $valor) : null;

        $respostas = [];
        foreach ($this->avaliacaoModel->perguntas() as $campo => $rotulo) {
            $respostas[$campo] = $bool($this->request->getPost($campo));
        }

        $dados = $respostas;
        $dados['acolhimento_id'] = $acolhimentoId;
        $dados['data_hora'] = date('Y-m-d H:i:s');
        $dados['profissional_id'] = (int) session()->get('usuario_id') > 0 ? (int) session()->get('usuario_id') : null;
        $dados['observacoes_profissionais'] = $limpar($this->request->getPost('observacoes_profissionais'));
        $dados['classificacao'] = $classificacao;
        $dados['classificacao_sugerida'] = $this->avaliacaoModel->sugestaoClassificacao($respostas);

        if (! $this->avaliacaoModel->save($dados)) {
            return redirect()->back()->withInput()->with('erros', $this->avaliacaoModel->errors());
        }

        return redirect()->to('/acolhimentos/' . $acolhimentoId)
            ->with('sucesso', 'Avaliação de risco registrada no acolhimento.');
    }

    public function detalhes(int $id)
    {
        $bloqueado = $this->somenteEquipe();
        if ($bloqueado !== null) {
            return $bloqueado;
        }

        $avaliacao = $this->avaliacaoModel->comDados()
            ->where('avaliacoes_risco.id', $id)
            ->first();

        if ($avaliacao === null) {
            return redirect()->to('/avaliacoes')->with('erro', 'Avaliação de risco não encontrada.');
        }

        $data = [
            'titulo'      => 'Avaliação de risco #' . $id,
            'avaliacao'   => $avaliacao,
            'perguntas'   => $this->avaliacaoModel->perguntas(),
            'riscos'      => AcolhimentoModel::RISCOS,
        ];

        return view('avaliacoes/detalhes', $data);
    }
}