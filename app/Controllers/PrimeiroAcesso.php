<?php

namespace App\Controllers;

use App\Models\ConviteMulherModel;
use App\Models\MulherModel;
use App\Models\UserModel;
use CodeIgniter\Database\ConnectionInterface;

/**
 * PRIMEIRO ACESSO da mulher cadastrada pela equipe.
 *
 * Rotas públicas (fora do grupo autenticado): a mulher chega aqui pelo link do
 * e-mail e ainda não tem conta, então não existe sessão nem senha para validar.
 *
 * O que este controller garante:
 *  - nada é criado sem um convite válido, dentro do prazo e ainda não usado;
 *  - a senha é digitada pela própria mulher e nunca trafega na URL;
 *  - o hash fica a cargo do UserModel (callback hashPassword);
 *  - a conta, o vínculo com a ficha e o consumo do convite acontecem juntos.
 */
class PrimeiroAcesso extends BaseController
{
    private ConviteMulherModel $conviteModel;
    private MulherModel $mulherModel;

    public function __construct()
    {
        $this->conviteModel = new ConviteMulherModel();
        $this->mulherModel  = new MulherModel();
    }

    /**
     * GET /primeiro-acesso/{token} — exibe a criação de senha.
     *
     * Rota pública de propósito: a mulher chega pelo link do e-mail sem conta
     * e sem sessão. A autorização é o token, não o login.
     *
     * Por isso NÃO existe guarda "se já estiver logado, volte ao dashboard"
     * aqui. A abertura de convites no computador da equipe acontece com a
     * equipe autenticada, e esse desvio jogava o operador no dashboard no
     * lugar da tela de criação de senha. Estar logado — como admin,
     * funcionário ou qualquer outra mulher — não diz nada sobre este convite,
     * e a sessão alheia permanece intacta.
     */
    public function index(string $token)
    {
        $convite = $this->conviteModel->porToken($token);
        $status  = $this->conviteModel->statusDe($convite);

        if ($status !== ConviteMulherModel::STATUS_VALIDO) {
            return $this->conviteInvalido($status);
        }

        $mulher = $this->mulherModel->find((int) $convite['mulher_id']);

        // Ficha removida depois do convite emitido.
        if ($mulher === null) {
            return $this->conviteInvalido('inexistente');
        }

        return view('auth/primeiro_acesso', [
            'titulo'     => 'Criar minha senha',
            'token'      => $token,
            'email'      => $this->emailExibido((string) ($mulher['email'] ?? '')),
            'expiraEm'   => (string) $convite['expira_em'],
        ]);
    }

    /**
     * POST /primeiro-acesso/{token} — grava a senha e cria a conta.
     */
    public function criar(string $token)
    {
        $convite = $this->conviteModel->porToken($token);
        $status  = $this->conviteModel->statusDe($convite);

        if ($status !== ConviteMulherModel::STATUS_VALIDO) {
            return $this->conviteInvalido($status);
        }

        $mulher = $this->mulherModel->find((int) $convite['mulher_id']);
        if ($mulher === null) {
            return $this->conviteInvalido('inexistente');
        }

        $erros = $this->validarSenha();
        if ($erros !== null) {
            return redirect()->back()->withInput()->with('erros', $erros);
        }

        // Quem já tem conta não ganha uma segunda (regra 18).
        if (! empty($mulher['user_id'])) {
            return $this->conviteInvalido(ConviteMulherModel::STATUS_UTILIZADO);
        }

        $email = trim((string) ($mulher['email'] ?? ''));
        if ($email === '') {
            return redirect()->back()->with('erros', ['email' => 'O cadastro não possui e-mail para criar a conta.']);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('erros', ['email' => 'O e-mail cadastrado é inválido.']);
        }

        // Impede conta conflitante com qualquer usuário já existente.
        $conflito = $this->emailEmUso($email, (int) $mulher['id']);
        if ($conflito !== null) {
            return redirect()->back()->with('erros', ['email' => $conflito]);
        }

        $senha = (string) $this->request->getPost('senha');

        /** @var ConnectionInterface $db */
        $db = \Config\Database::connect();
        $db->transException(false);
        $db->transBegin();

        try {
            // UserModel hasheia a senha no beforeInsert.
            $userModel = new UserModel();
            $userId    = $userModel->insert([
                'nome'   => (string) $mulher['nome_completo'],
                'email'  => $email,
                'senha'  => $senha,
                'perfil' => 'mulher',
                'ativo'  => 1,
            ]);

            if ($userId === false || $userId === null) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('erros', ['geral' => 'Não foi possível criar a conta. Tente novamente.']);
            }

            $vinculado = $this->mulherModel->update((int) $mulher['id'], [
                'user_id' => (int) $userId,
            ]);

            if ($vinculado === false) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('erros', ['geral' => 'Não foi possível vincular a conta. Tente novamente.']);
            }

            // Consumo do convite (uso único) na mesma transação da conta.
            if (! $this->conviteModel->marcarUtilizado((int) $convite['id'])) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('erros', ['geral' => 'Não foi possível concluir o primeiro acesso.']);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('erros', ['geral' => 'Não foi possível criar a conta. Tente novamente.']);
        }

        // Não é logada a senha nem o token: só o desfecho do primeiro acesso.
        $this->auditar(
            'PRIMEIRO_ACESSO',
            'primeiro_acesso',
            (int) $userId,
            'mulher',
            (string) $mulher['nome_completo'],
            ['user_id' => (int) $userId, 'mulher_id' => (int) $mulher['id']],
            (int) $userId
        );

        return redirect()->to('/login')
            ->with('sucesso', 'Conta criada com sucesso! Faça login com o e-mail e a senha que você acabou de definir.');
    }

    /**
     * Regras de senha do primeiro acesso. Espelham o `UserModel` e o cadastro
     * público para que a mulher não seja aceita aqui e recusada no login.
     *
     * @return array<string, string>|null
     */
    private function validarSenha(): ?array
    {
        $regras = [
            'senha'          => 'required|min_length[6]|max_length[72]',
            'confirma_senha' => 'required',
        ];

        $mensagens = [
            'senha' => [
                'required'   => 'Crie uma senha para acessar o sistema.',
                'min_length' => 'A senha deve ter no mínimo 6 caracteres.',
                'max_length' => 'A senha deve ter no máximo 72 caracteres.',
            ],
            'confirma_senha' => ['required' => 'Confirme a senha.'],
        ];

        if (! $this->validate($regras, $mensagens)) {
            return $this->validator->getErrors();
        }

        if ((string) $this->request->getPost('senha') !== (string) $this->request->getPost('confirma_senha')) {
            return ['confirma_senha' => 'As senhas não conferem.'];
        }

        return null;
    }

    /**
     * Detecta e-mail que já geraria uma conta conflitante: pertence a um
     * usuário da equipe ou a outra ficha de mulher. A comparação ignora
     * maiúsculas, igual à regra de unicidade do banco.
     *
     * @return string|null mensagem de erro, ou null quando está livre
     */
    private function emailEmUso(string $email, int $mulherId): ?string
    {
        $db = db_connect();

        $usuario = $db
            ->table('users')
            ->where('LOWER(email)', mb_strtolower($email))
            ->get()
            ->getRowArray();

        if ($usuario !== null && (string) $usuario['perfil'] !== 'mulher') {
            return 'Este e-mail já está em uso por um usuário do sistema.';
        }

        $outra = $db
            ->table('dados_mulher')
            ->where('id !=', $mulherId)
            ->groupStart()
            ->where('email IS NOT NULL')
            ->where('LOWER(email)', mb_strtolower($email))
            ->groupEnd()
            ->get()
            ->getRowArray();

        if ($outra !== null) {
            return 'Este e-mail já consta de outro cadastro, então não é possível criar a conta por aqui. '
                . 'Peça à equipe da Procuradoria da Mulher para verificar a duplicidade e reenviar o convite.';
        }

        return null;
    }

    /**
     * Tela de convite recusado. O status HTTP reflete o motivo, mas a página
     * é sempre a mesma — nada do que o link carrega é revelado aqui.
     */
    private function conviteInvalido(string $status)
    {
        [$codigo, $mensagem] = match ($status) {
            ConviteMulherModel::STATUS_UTILIZADO => [
                410,
                'Este convite já foi utilizado. A sua conta de acesso já foi criada - basta fazer login.',
            ],
            ConviteMulherModel::STATUS_INVALIDADO => [
                410,
                'Este link foi substituído por um convite mais recente. Peça à equipe da Procuradoria da Mulher para reenviar o convite de primeiro acesso.',
            ],
            ConviteMulherModel::STATUS_EXPIRADO => [
                410,
                'Este convite expirou. Peça à equipe da Procuradoria da Mulher para enviar um novo convite de primeiro acesso.',
            ],
            default => [404, 'Link de primeiro acesso inválido.'],
        };

        // view() devolve string, então o status HTTP vai no response().
        $this->response->setStatusCode($codigo);

        return view('auth/primeiro_acesso', [
            'titulo'          => 'Convite indisponível',
            'conviteInvalido' => $mensagem,
        ]);
    }

    /**
     * Exibe apenas o e-mail mascarado: identifica a conta sem despejar o
     * endereço completo numa tela que pode estar sendo vista por terceiros.
     */
    private function emailExibido(string $email): string
    {
        $email = trim($email);
        if ($email === '') {
            return '—';
        }

        $arroba = strpos($email, '@');
        if ($arroba === false || $arroba < 1) {
            return '—';
        }

        $usuario = substr($email, 0, $arroba);
        $dominio = substr($email, $arroba + 1);
        $visivel = mb_substr($usuario, 0, min(3, mb_strlen($usuario)));

        return $visivel . str_repeat('*', max(3, mb_strlen($usuario) - mb_strlen($visivel))) . '@' . $dominio;
    }
}
