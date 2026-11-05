<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\MulherModel;

class MulherController extends BaseController
{
    public function cadastro()
    {
        if (session()->get('logado')) {
            $perfil = session()->get('perfil');
            if ($perfil === 'admin' || $perfil === 'funcionario') {
                return redirect()->to('/dashboard');
            }
            if ($perfil === 'mulher') {
                return redirect()->to('/mulher/dashboard');
            }
        }

        $data = [
            'titulo' => 'Cadastro da Mulher',
        ];

        return view('cadastro/form', $data);
    }

    public function registrar()
    {
        $userModel = new UserModel();
        $mulherModel = new MulherModel();

        // A unicidade do e-mail NÃO entra aqui: `dados_mulher` também guarda
        // mulheres que a equipe cadastrou e que ainda não têm conta, e elas
        // nunca aparecem em `users`. A regra completa está em conflitosDeIdentidade().
        $regras = [
            'nome_completo' => 'required|max_length[200]',
            'email'         => 'required|valid_email',
            'senha'         => 'required|min_length[6]',
            'confirma_senha' => 'required',
            'data_nascimento' => 'permit_empty|valid_date',
        ];

        $mensagens = [
            'nome_completo' => ['required' => 'O nome completo é obrigatório.'],
            'email' => [
                'required'   => 'O e-mail é obrigatório.',
                'valid_email' => 'Informe um e-mail válido.',
            ],
            'senha' => [
                'required'   => 'A senha é obrigatória.',
                'min_length' => 'A senha deve ter no mínimo 6 caracteres.',
            ],
            'confirma_senha' => [
                'required' => 'A confirmação de senha é obrigatória.',
            ],
            'data_nascimento' => [
                'valid_date' => 'Data de nascimento inválida.',
            ],
        ];

        if (! $this->validate($regras, $mensagens)) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        // Antes de gravar qualquer coisa: uma mulher só tem uma ficha, e a
        // ficha da equipe é a dela. Sem esta checagem, uma mulher já atendida
        // que perde o e-mail do convite acabava criando a segunda ficha por
        // aqui — e a duplicidade bloqueava o primeiro acesso dela.
        $errosIdentidade = $this->conflitosDeIdentidade();
        if ($errosIdentidade !== []) {
            return redirect()->back()->withInput()->with('erros', $errosIdentidade);
        }

        $senha = $this->request->getPost('senha');
        $confirmaSenha = $this->request->getPost('confirma_senha');

        if ($senha !== $confirmaSenha) {
            return redirect()->back()->withInput()->with('erros', ['confirma_senha' => 'As senhas não conferem.']);
        }

        $db = \Config\Database::connect();
        $db->transException(true);
        $db->transStart();

        // Criar usuário
        $userId = $userModel->insert([
            'nome'    => $this->request->getPost('nome_completo'),
            'email'   => $this->request->getPost('email'),
            'senha'   => $senha,
            'perfil'  => 'mulher',
            'ativo'   => 1,
        ]);

        if ($userId === false) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('erros', ['general' => 'Erro ao criar conta. Tente novamente.']);
        }

        // Calcular idade a partir da data de nascimento
        $idade = null;
        $dataNascimento = $this->request->getPost('data_nascimento');
        if (! empty($dataNascimento)) {
            $nascimento = new \DateTime($dataNascimento);
            $hoje = new \DateTime();
            $idade = $nascimento->diff($hoje)->y;
        }

        // Salvar dados da mulher
        $dadosMulher = [
            'user_id'               => $userId,
            'nome_completo'         => $this->request->getPost('nome_completo'),
            'nome_social'           => $this->request->getPost('nome_social') ?: null,
            'cpf'                   => $this->request->getPost('cpf') ?: null,
            'rg'                    => $this->request->getPost('rg') ?: null,
            'data_nascimento'       => $dataNascimento ?: null,
            'sexo'                  => $this->request->getPost('sexo') ?: null,
            'estado_civil'          => $this->request->getPost('estado_civil') ?: null,
            'escolaridade'          => $this->request->getPost('escolaridade') ?: null,
            'profissao'             => $this->request->getPost('profissao') ?: null,
            'ocupacao'              => $this->request->getPost('ocupacao') ?: null,
            'telefone'              => $this->request->getPost('telefone') ?: null,
            'whatsapp'              => $this->request->getPost('whatsapp') ?: null,
            'email'                 => $this->request->getPost('email'),
            'cep'                   => $this->request->getPost('cep') ?: null,
            'logradouro'            => $this->request->getPost('logradouro') ?: null,
            'numero'                => $this->request->getPost('numero') ?: null,
            'complemento'           => $this->request->getPost('complemento') ?: null,
            'bairro'                => $this->request->getPost('bairro') ?: null,
            'municipio'             => $this->request->getPost('municipio') ?: null,
            'estado'                => $this->request->getPost('estado') ?: null,
            'ponto_referencia'      => $this->request->getPost('ponto_referencia') ?: null,
            'zona'                  => $this->request->getPost('zona') ?: null,
            'possui_filhos'         => $this->request->getPost('possui_filhos') ? 1 : 0,
            'esta_gravida'          => $this->request->getPost('esta_gravida') ? 1 : 0,
            'possui_deficiencia'    => $this->request->getPost('possui_deficiencia') ? 1 : 0,
            'e_idosa'               => $this->request->getPost('e_idosa') ? 1 : 0,
            'situacao_vulnerabilidade' => $this->request->getPost('situacao_vulnerabilidade') ? 1 : 0,
            'observacoes'           => $this->request->getPost('observacoes') ?: null,
        ];

        $mulherId = $mulherModel->insert($dadosMulher);

        if ($mulherId === false) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('erros', ['general' => 'Erro ao salvar dados. Tente novamente.']);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('erros', ['general' => 'Erro ao processar cadastro. Tente novamente.']);
        }

        return redirect()->to('/login')->with('sucesso', 'Cadastro realizado com sucesso! Faça login para acessar o sistema.');
    }

    /**
     * Regras de identidade do cadastro público.
     *
     * `dados_mulher` guarda UMA ficha por mulher, e a ficha pode existir sem
     * conta — é o caso normal de quem a equipe atendeu e ainda espera o
     * primeiro acesso. Conferir só `users` (como fazia o `is_unique`)
     * deixava passar exatamente o caso que duplicava ficha: a mulher atendida
     * pela equipe ainda não tem linha em `users`.
     *
     * O e-mail identifica a ficha existente e o CPF confirma a identidade.
     * Nenhum dos dois autoriza nada aqui: quem cria a conta sobre a ficha da
     * equipe é o token do convite, no fluxo de primeiro acesso. Este método
     * apenas recusa e diz o caminho certo — nunca consome o convite nem o
     * assume.
     *
     * @return array<string, string> erros por campo; vazio quando está livre
     */
    private function conflitosDeIdentidade(): array
    {
        $erros = [];

        $mulheres = new MulherModel();
        $email = trim((string) $this->request->getPost('email'));
        $cpf   = trim((string) $this->request->getPost('cpf'));

        // 1) CPF identifica a pessoa: uma ficha por CPF, com ou sem conta.
        if ($mulheres->buscarPorCpf($cpf) !== null) {
            $erros['cpf'] = 'Já existe uma ficha com este CPF.';
        }

        // 2) E-mail já com conta em `users`: é login, não cadastro novo.
        $conta = db_connect()
            ->table('users')
            ->where('LOWER(email)', mb_strtolower($email))
            ->get()
            ->getRowArray();

        if ($conta !== null) {
            $erros['email'] = (string) $conta['perfil'] === 'mulher'
                ? 'Já existe uma conta de acesso com este e-mail. Faça login ou use a recuperação de acesso.'
                : 'Este e-mail já está em uso por um usuário do sistema.';

            return $erros;
        }

        // 3) E-mail de ficha SEM conta: a equipe já atendeu essa mulher, e a
        // senha dela é definida pelo convite que ela recebeu — não por aqui.
        $ficha = $mulheres
            ->groupStart()
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->where('LOWER(email)', mb_strtolower($email))
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->first();

        if ($ficha !== null) {
            $erros['email'] = empty((string) $ficha['user_id'])
                ? 'Já existe um cadastro seu com este e-mail. Use o convite de primeiro acesso enviado pela equipe para criar sua senha.'
                : 'Já existe uma conta de acesso com este e-mail. Faça login ou use a recuperação de acesso.';
        }

        return $erros;
    }

    public function dashboard()
    {
        $userId = (int) session()->get('usuario_id');
        $mulherModel = new MulherModel();
        $dadosMulher = $mulherModel->findByUserId($userId);

        $data = [
            'titulo'      => 'Dashboard',
            'dadosMulher' => $dadosMulher,
        ];

        return view('mulher/dashboard', $data);
    }

    public function perfil()
    {
        $userId = (int) session()->get('usuario_id');
        $mulherModel = new MulherModel();
        $dadosMulher = $mulherModel->findByUserId($userId);

        $data = [
            'titulo'      => 'Meus Dados',
            'dadosMulher' => $dadosMulher,
        ];

        return view('mulher/perfil', $data);
    }

    public function atualizarPerfil()
    {
        $userId = (int) session()->get('usuario_id');
        $mulherModel = new MulherModel();
        $dadosMulher = $mulherModel->findByUserId($userId);

        if ($dadosMulher === null) {
            return redirect()->to('/mulher/dashboard')->with('erro', 'Dados não encontrados.');
        }

        $mulherModel->update($dadosMulher['id'], [
            'nome_social'          => $this->request->getPost('nome_social') ?: null,
            'telefone'             => $this->request->getPost('telefone') ?: null,
            'whatsapp'             => $this->request->getPost('whatsapp') ?: null,
            'cep'                  => $this->request->getPost('cep') ?: null,
            'logradouro'           => $this->request->getPost('logradouro') ?: null,
            'numero'               => $this->request->getPost('numero') ?: null,
            'complemento'          => $this->request->getPost('complemento') ?: null,
            'bairro'               => $this->request->getPost('bairro') ?: null,
            'municipio'            => $this->request->getPost('municipio') ?: null,
            'estado'               => $this->request->getPost('estado') ?: null,
            'ponto_referencia'     => $this->request->getPost('ponto_referencia') ?: null,
            'zona'                 => $this->request->getPost('zona') ?: null,
            'observacoes'          => $this->request->getPost('observacoes') ?: null,
        ]);

        return redirect()->to('/mulher/perfil')->with('sucesso', 'Dados atualizados com sucesso.');
    }
}
