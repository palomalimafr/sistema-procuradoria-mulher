<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * CONFIGURAÇÕES do sistema (SISTEMA > Configurações).
 *
 * Acesso restrito ao perfil administrador (filtro 'admin' nas rotas).
 * Persiste as informações institucionais e gerais na tabela
 * `sistema_configuracoes` (chave -> valor). O logo é gravado fora do docroot
 * (WRITEPATH/uploads/logo) com nome físico aleatório, no mesmo padrão do
 * módulo Documentos.
 */
class Configuracoes extends BaseController
{
    protected $configuracaoModel;

    public function __construct()
    {
        $this->configuracaoModel = new ConfiguracaoModel();
    }

    public function index()
    {
        $data = [
            'titulo' => 'Configurações',
            'configs' => $this->configuracaoModel->obterTodos(),
            'smtpConfigurado' => $this->smtpConfigurado(),
        ];

        return view('configuracoes/index', $data);
    }

    /**
     * Salva as configurações enviadas pelo formulário. O logo é opcional:
     * só é substituído quando um novo arquivo válido é enviado.
     */
    public function salvar()
    {
        $regras = [
            'nome_sistema'      => 'required|max_length[100]',
            'nome_instituicao'  => 'required|max_length[150]',
            'nome_procuradoria' => 'required|max_length[150]',
            'telefone'          => 'permit_empty|max_length[30]',
            'email'             => 'permit_empty|valid_email|max_length[150]',
            'endereco'          => 'permit_empty|max_length[255]',
            'municipio'         => 'permit_empty|max_length[150]',
            'estado'            => 'permit_empty|in_list[' . implode(',', self::UFS) . ']',
            'email_smtp_host'   => 'permit_empty|max_length[255]',
            'email_smtp_port'   => 'permit_empty|numeric|is_natural|less_than_equal_to[65535]',
            'email_smtp_user'   => 'permit_empty|max_length[150]',
            'email_smtp_pass'   => 'permit_empty|max_length[255]',
            'email_smtp_crypto' => 'permit_empty|in_list[tls,ssl,]',
            'email_remetente_email' => 'permit_empty|valid_email|max_length[150]',
            'email_remetente_nome'  => 'permit_empty|max_length[150]',
            'email_convite_validade_horas' => 'permit_empty|numeric|is_natural|less_than_equal_to[8760]',
        ];

        $logo = $this->request->getFile('logo');
        $temLogo = $logo instanceof UploadedFile && $logo->getError() !== UPLOAD_ERR_NO_FILE;

        if ($temLogo) {
            $regras['logo'] = 'is_image[logo]'
                . '|max_size[logo,2048]'
                . '|ext_in[logo,png,jpg,jpeg,gif,webp]'
                . '|mime_in[logo,image/png,image/jpeg,image/gif,image/webp]';
        }

        if (! $this->validate($regras, $this->mensagens())) {
            return redirect()->to('/sistema/configuracoes')
                ->withInput()
                ->with('erros', $this->validator->getErrors());
        }

        $campos = [];
        foreach (array_keys(ConfiguracaoModel::DEFAULTS) as $chave) {
            if ($chave === 'logo') {
                continue;
            }

            $val = $this->request->getPost($chave);
            $limpo = $val !== null && trim((string) $val) !== '' ? trim((string) $val) : null;

            // Senha do SMTP é segredo: campo vazio significa "manter a que já
            // está gravada", nunca "apagar". Sem isso, salvar a tela por causa
            // de outro campo quebraria o envio de e-mail.
            if ($limpo === null && in_array($chave, ConfiguracaoModel::CHAVES_SECRETAS, true)) {
                continue;
            }

            $campos[$chave] = $limpo;
        }

        $caminhoLogo = null;
        if ($temLogo) {
            $caminhoLogo = $this->gravarLogo($logo);
            if ($caminhoLogo === null) {
                return redirect()->to('/sistema/configuracoes')
                    ->withInput()
                    ->with('erros', ['logo' => 'Não foi possível salvar o logo no servidor.']);
            }
        }

        $this->configuracaoModel->salvarCampos($campos);

        if ($caminhoLogo !== null) {
            $antigo = $this->configuracaoModel->obter('logo');
            $campos['logo'] = $caminhoLogo;
            $this->configuracaoModel->salvarCampos($campos);

            if ($antigo !== null && $antigo !== $caminhoLogo) {
                $this->removerArquivoFisico($antigo);
            }
        }

        return redirect()->to('/sistema/configuracoes')
            ->with('sucesso', 'Configurações salvas com sucesso.');
    }

    /**
     * Remove o logo cadastrado e apaga o arquivo físico correspondente.
     */
    public function removerLogo()
    {
        $atual = $this->configuracaoModel->obter('logo');
        if ($atual !== null && $atual !== '') {
            $this->configuracaoModel->salvarCampos(['logo' => null]);
            $this->removerArquivoFisico($atual);
        }

        return redirect()->to('/sistema/configuracoes')
            ->with('sucesso', 'Logo removido com sucesso.');
    }

    /**
     * Serve o logo cadastrado (usado pelo layout na área de marca).
     */
    public function logo()
    {
        $caminho = $this->configuracaoModel->obter('logo');
        if ($caminho === null || ! str_starts_with($caminho, 'logo/')) {
            return $this->response->setStatusCode(404);
        }

        $arquivo = $this->caminhoArquivo($caminho);
        if (! is_file($arquivo)) {
            return $this->response->setStatusCode(404);
        }

        $mime = \Config\Mimes::guessTypeFromExtension(pathinfo($arquivo, PATHINFO_EXTENSION)) ?? 'image/png';

        return $this->response
            ->setContentType($mime)
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($arquivo));
    }

    /**
     * Indica se já existe credencial SMTP gravada. A senha nunca volta para a
     * tela: o campo é sempre reenviado vazio e o valor só é substituído
     * quando a equipe digita uma nova senha.
     */
    private function smtpConfigurado(): bool
    {
        $host = $this->configuracaoModel->obter('email_smtp_host');
        $user = $this->configuracaoModel->obter('email_smtp_user');
        $pass = $this->configuracaoModel->obter('email_smtp_pass');

        return $host !== null && $user !== null && $pass !== null;
    }

    private function mensagens(): array
    {
        return [
            'nome_sistema'      => ['required' => 'Informe o nome do sistema.'],
            'nome_instituicao'  => ['required' => 'Informe o nome da instituição.'],
            'nome_procuradoria' => ['required' => 'Informe o nome da Procuradoria da Mulher.'],
            'email'             => ['valid_email' => 'Informe um e-mail institucional válido.'],
            'estado'            => ['in_list' => 'Selecione um estado (UF) válido.'],
            'email_smtp_port'   => [
                'numeric'             => 'A porta do SMTP deve ser um número.',
                'is_natural'          => 'A porta do SMTP deve ser um número positivo.',
                'less_than_equal_to' => 'A porta do SMTP deve ser menor ou igual a 65535.',
            ],
            'email_smtp_crypto' => ['in_list' => 'Selecione o tipo de criptografia do SMTP.'],
            'email_remetente_email' => ['valid_email' => 'Informe um e-mail de remetente válido.'],
            'email_convite_validade_horas' => [
                'numeric'             => 'A validade do convite deve ser um número de horas.',
                'is_natural'          => 'A validade do convite deve ser um número positivo.',
                'less_than_equal_to' => 'A validade do convite deve ser menor ou igual a 8760 horas (1 ano).',
            ],
            'logo'              => [
                'is_image' => 'O arquivo enviado não é uma imagem válida.',
                'max_size' => 'A imagem excede o tamanho máximo permitido de 2 MB.',
                'ext_in'   => 'Formato de imagem não permitido (use PNG, JPG, GIF ou WEBP).',
                'mime_in'  => 'O tipo (MIME) da imagem não é permitido.',
            ],
        ];
    }

    /**
     * Persiste o logo fora do docroot com nome físico aleatório (padrão do
     * módulo Documentos). Aceita POST real e arquivos criados em testes (CLI).
     *
     * @return string|null caminho relativo a WRITEPATH/uploads em caso de sucesso
     */
    private function gravarLogo(UploadedFile $arquivo): ?string
    {
        $dir = WRITEPATH . 'uploads/logo';

        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return null;
        }

        if (! is_file($dir . '/index.html')) {
            @file_put_contents($dir . '/index.html', '403 Forbidden');
        }

        $extensao = mb_strtolower($arquivo->getExtension());
        $nome = bin2hex(random_bytes(16)) . ($extensao !== '' ? '.' . $extensao : '');
        $destino = $dir . '/' . $nome;

        $temp = $arquivo->getTempName();
        $ok = is_uploaded_file($temp) ? move_uploaded_file($temp, $destino) : rename($temp, $destino);

        if (! $ok) {
            return null;
        }

        return 'logo/' . $nome;
    }

    private function caminhoArquivo(string $relativo): string
    {
        return WRITEPATH . 'uploads/' . ltrim($relativo, '/');
    }

    private function removerArquivoFisico(string $relativo): void
    {
        if (! str_starts_with($relativo, 'logo/')) {
            return;
        }

        $arquivo = $this->caminhoArquivo($relativo);
        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }

    private const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
        'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
        'SP', 'SE', 'TO',
    ];
}