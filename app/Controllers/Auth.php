<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('logado')) {
            return redirect()->to('/dashboard');
        }

        $data = [
            'titulo' => 'Login',
        ];

        return view('auth/login', $data);
    }

    public function autenticar()
    {
        $email = $this->request->getPost('email');
        $senha = $this->request->getPost('senha');

        if ($email === null || $senha === null) {
            return redirect()->back()->with('erro', 'Informe e-mail e senha.');
        }

        $model = new UserModel();
        $usuario = $model->where('email', $email)->where('ativo', 1)->first();

        if ($usuario !== null && password_verify($senha, $usuario['senha'])) {
            session()->set([
                'logado'   => true,
                'usuario_id' => $usuario['id'],
                'usuario_nome' => $usuario['nome'],
                'usuario_email' => $usuario['email'],
                'perfil'   => $usuario['perfil'],
            ]);

            $this->auditar(
                'LOGIN',
                'auth',
                (int) $usuario['id'],
                'usuario',
                (string) $usuario['nome'],
                ['perfil' => (string) $usuario['perfil']],
                (int) $usuario['id']
            );

            $perfil = $usuario['perfil'];
            if ($perfil === 'mulher') {
                $destino = '/mulher/dashboard';
            } else {
                $destino = '/dashboard';
            }

            return redirect()->to($destino)->with('sucesso', 'Bem-vindo(a), ' . $usuario['nome'] . '!');
        }

        return redirect()->back()->with('erro', 'E-mail ou senha inválidos.');
    }

    public function logout()
    {
        $usuarioId = session()->has('usuario_id') ? (int) session()->get('usuario_id') : null;
        $nome = session()->has('usuario_nome') ? (string) session()->get('usuario_nome') : '';

        session()->destroy();

        $this->auditar(
            'LOGOUT',
            'auth',
            $usuarioId,
            'usuario',
            $usuarioId !== null ? (string) $usuarioId : null,
            $nome !== '' ? ['nome' => $nome] : [],
            $usuarioId
        );

        return redirect()->to('/login')->with('sucesso', 'Sessão encerrada com sucesso.');
    }
}
