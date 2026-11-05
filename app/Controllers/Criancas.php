<?php

namespace App\Controllers;

use App\Models\CriancaModel;
use App\Models\ResponsavelModel;

class Criancas extends BaseController
{
    protected $criancaModel;
    protected $responsavelModel;

    public function __construct()
    {
        $this->criancaModel = new CriancaModel();
        $this->responsavelModel = new ResponsavelModel();
    }

    public function index()
    {
        $data = [
            'titulo'   => 'Crianças Assistidas',
            'criancas' => $this->criancaModel->orderBy('nome', 'ASC')->paginate(10),
            'pager'    => $this->criancaModel->pager,
        ];

        return view('criancas/index', $data);
    }

    public function novo()
    {
        $data = [
            'titulo' => 'Nova Criança',
        ];

        return view('criancas/form', $data);
    }

    public function criar()
    {
        if (! $this->validate([
            'nome' => 'required|min_length[3]|max_length[150]',
        ])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $this->criancaModel->save($this->request->getPost());

        return redirect()->to('/criancas')->with('sucesso', 'Criança cadastrada com sucesso.');
    }

    public function editar(int $id)
    {
        $crianca = $this->criancaModel->find($id);

        if ($crianca === null) {
            return redirect()->to('/criancas')->with('erro', 'Criança não encontrada.');
        }

        $data = [
            'titulo'  => 'Editar Criança',
            'crianca' => $crianca,
            'responsaveis' => $this->responsavelModel->where('crianca_id', $id)->findAll(),
        ];

        return view('criancas/form', $data);
    }

    public function atualizar(int $id)
    {
        if (! $this->validate([
            'nome' => 'required|min_length[3]|max_length[150]',
        ])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $this->criancaModel->update($id, $this->request->getPost());

        return redirect()->to('/criancas')->with('sucesso', 'Criança atualizada com sucesso.');
    }

    public function excluir(int $id)
    {
        $this->criancaModel->delete($id);

        return redirect()->to('/criancas')->with('sucesso', 'Criança excluída com sucesso.');
    }

    public function detalhes(int $id)
    {
        $crianca = $this->criancaModel->find($id);

        if ($crianca === null) {
            return redirect()->to('/criancas')->with('erro', 'Criança não encontrada.');
        }

        $data = [
            'titulo'  => 'Detalhes da Criança',
            'crianca' => $crianca,
            'responsaveis' => $this->responsavelModel->where('crianca_id', $id)->findAll(),
        ];

        return view('criancas/detalhes', $data);
    }

    public function adicionarResponsavel(int $criancaId)
    {
        $this->responsavelModel->save([
            'crianca_id' => $criancaId,
            'nome'       => $this->request->getPost('nome'),
            'parentesco' => $this->request->getPost('parentesco'),
            'cpf'        => $this->request->getPost('cpf'),
            'telefone'   => $this->request->getPost('telefone'),
            'endereco'   => $this->request->getPost('endereco'),
        ]);

        return redirect()->back()->with('sucesso', 'Responsável adicionado com sucesso.');
    }

    public function excluirResponsavel(int $id)
    {
        $this->responsavelModel->delete($id);

        return redirect()->back()->with('sucesso', 'Responsável removido.');
    }
}
