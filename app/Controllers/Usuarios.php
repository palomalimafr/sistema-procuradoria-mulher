<?php

namespace App\Controllers;

use App\Models\UserModel;

class Usuarios extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $data = [
            'titulo'  => 'Usuários',
            'usuarios' => $this->userModel->orderBy('nome', 'ASC')->paginate(10),
            'pager'   => $this->userModel->pager,
        ];

        return view('usuarios/index', $data);
    }

    public function novo()
    {
        $data = [
            'titulo' => 'Novo Usuário',
        ];

        return view('usuarios/form', $data);
    }

    public function criar()
    {
        if (! $this->validate([
            'nome'  => 'required|min_length[3]|max_length[150]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'senha' => 'required|min_length[6]',
            'perfil' => 'required|in_list[admin,funcionario]',
        ])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $this->userModel->save($this->request->getPost());

        return redirect()->to('/usuarios')->with('sucesso', 'Usuário criado com sucesso.');
    }

    public function editar(int $id)
    {
        $usuario = $this->userModel->find($id);

        if ($usuario === null) {
            return redirect()->to('/usuarios')->with('erro', 'Usuário não encontrado.');
        }

        $data = [
            'titulo'  => 'Editar Usuário',
            'usuario' => $usuario,
        ];

        return view('usuarios/form', $data);
    }

    public function atualizar(int $id)
    {
        if (! $this->validate([
            'nome'  => 'required|min_length[3]|max_length[150]',
            'email' => "required|valid_email|is_unique[users.email,id,{$id}]",
            'perfil' => 'required|in_list[admin,funcionario]',
        ])) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        $dados = $this->request->getPost();

        // Se a senha veio vazia, mantém a atual
        if (empty($dados['senha'])) {
            unset($dados['senha']);
        }

        $this->userModel->update($id, $dados);

        return redirect()->to('/usuarios')->with('sucesso', 'Usuário atualizado com sucesso.');
    }

    public function excluir(int $id)
    {
        if ($id === (int) session()->get('usuario_id')) {
            return redirect()->to('/usuarios')->with('erro', 'Você não pode excluir o próprio usuário.');
        }

        $this->userModel->delete($id);

        return redirect()->to('/usuarios')->with('sucesso', 'Usuário excluído com sucesso.');
    }
}
