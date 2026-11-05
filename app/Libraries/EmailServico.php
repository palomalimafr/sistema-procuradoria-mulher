<?php

namespace App\Libraries;

use App\Models\ConfiguracaoModel;
use CodeIgniter\Email\Email;
use Throwable;

/**
 * Envio de e-mail do sistema.
 *
 * Único ponto que fala com o transporte de e-mail: qualquer módulo que precise
 * avisar um usuário passa por aqui, sem repetir montagem de remetente,
 * cabeçalho ou tratamento de falha.
 *
 * As credenciais NUNCA ficam no código. Tudo vem das configurações gravadas em
 * `sistema_configuracoes` (SISTEMA > Configurações), com recuo para
 * `Config\Email` (que por sua vez lê o `.env`) quando a chave não foi salva.
 */
class EmailServico
{
    /** Mensagem do último envio fallho, útil para exibir ao operador. */
    private ?string $ultimoErro = null;

    private ConfiguracaoModel $configuracoes;

    public function __construct(?ConfiguracaoModel $configuracoes = null)
    {
        $this->configuracoes = $configuracoes ?? new ConfiguracaoModel();
        $this->ultimoErro    = null;
    }

    /**
     * Informa se o serviço tem remetente utilizável. Sem isso o envio sempre
     * falha e o chamador precisa avisar antes de prometer acesso à mulher.
     */
    public function configurado(): bool
    {
        return $this->remetente()['email'] !== null;
    }

    /**
     * Remetente effective. Usa a configuração salva e, como último recurso, o
     * e-mail institucional do próprio sistema.
     *
     * @return array{email: string|null, nome: string}
     */
    public function remetente(): array
    {
        $email = $this->configuracoes->obter('email_remetente_email');
        if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $this->configuracoes->obter('email');
        }

        return [
            'email' => ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null,
            'nome'  => $this->configuracoes->obter('email_remetente_nome')
                ?? $this->configuracoes->obter('nome_procuradoria')
                ?? 'Procuradoria da Mulher',
        ];
    }

    /**
     * Prazo de validade do convite quando a equipe configurou um valor.
     *
     * Devolve null para "não configurado": nesse caso quem aplica o padrão é o
     * ConviteMulherModel, dono da regra, sem precisar replicá-la aqui.
     */
    public function validadeConviteHoras(): ?int
    {
        $configurado = $this->configuracoes->obter('email_convite_validade_horas');

        if ($configurado === null || trim($configurado) === '') {
            return null;
        }

        $valor = (int) $configurado;

        return $valor > 0 ? $valor : null;
    }

    /**
     * Envia uma mensagem. Devolve false em vez de lançar exceção: quem chama
     * decide o que fazer com a falha (neste projeto, desfazer o cadastro).
     *
     * Nunca registra senha ou token em log; em caso de erro só o motivo
     * técnico do transporte é guardado.
     */
    public function enviar(string $paraEmail, string $assunto, string $corpoHtml, ?string $corpoTexto = null): bool
    {
        $this->ultimoErro = null;

        if (! filter_var($paraEmail, FILTER_VALIDATE_EMAIL)) {
            $this->ultimoErro = 'Endereço de destinatário inválido.';

            return false;
        }

        $remetente = $this->remetente();
        if ($remetente['email'] === null) {
            $this->ultimoErro = 'Remetente não configurado em SISTEMA > Configurações.';

            return false;
        }

        // Falha de ambiente, não de configuração: sem openssl o PHP não
        // consegue criptografar nada e o envio morre no STARTTLS, mesmo com
        // host, porta e credenciais corretos. Só vale quando a equipe pediu
        // criptografia de fato — em "Nenhuma" não há TLS a negociar.
        if ($this->hostSmtp() !== null && $this->criptografia() !== '' && ! $this->tlsSuportado()) {
            $this->ultimoErro = 'O PHP deste servidor não tem a extensão openssl habilitada, '
                . 'então não consegue abrir conexão SMTP com TLS. '
                . 'Isso não é problema de configuração do e-mail: habilite openssl no PHP.';

            return false;
        }

        try {
            // O serviço do framework é reaproveitado para permitir mock em
            // testes; initialize() recarrega as configurações a cada envio.
            $email = service('email');
            $email->initialize($this->configuracaoTransporte());
            $email->setFrom($remetente['email'], $remetente['nome']);
            $email->setTo($paraEmail);
            $email->setSubject($assunto);

            if ($corpoTexto !== null && $corpoTexto !== '') {
                $email->setMessage($corpoHtml);
                $email->setAltMessage($corpoTexto);
            } else {
                $email->setMessage($corpoHtml);
            }

            if ($email->send(false)) {
                return true;
            }

            // Sem o motivo real do transporte, o operador só vê "falhou" e não
            // sabe se é TLS, porta, autenticação ou recusa do servidor. A
            // conversa SMTP é levantada sem cabeçalho/corpo (o corpo traz o
            // token do convite) e sem qualquer credencial.
            $this->ultimoErro = $this->motivoDoTransporte($email);

            return false;
        } catch (Throwable $e) {
            $this->ultimoErro = 'Falha no envio: ' . $this->semSegredos($e->getMessage());

            return false;
        }
    }

    /**
     * Extrai do transporte a causa técnica da recusa, já higienizada.
     *
     * A conversa bruta do SMTP é dominada por eco de comando (EHLO, STARTTLS) e
     * por capability lines que não ajudam a decidir nada. Quem descreve a causa
     * são as mensagens de erro que o próprio transporte anexa; os blocos
     * `<pre>…</pre>` de cada comando saem fora, junto com o corpo da mensagem.
     */
    private function motivoDoTransporte(object $email): string
    {
        // printDebugger([]) devolve só a conversa SMTP, sem cabeçalho e sem
        // corpo — o corpo carregaria o token do convite.
        if (! method_exists($email, 'printDebugger')) {
            return 'O servidor de e-mail recusou ou não respondeu à mensagem.';
        }

        $semEco = strip_tags(
            (string) preg_replace('#<pre>.*?</pre>#s', '', $email->printDebugger([]))
        );

        $motivo = $this->semSegredos(
            trim((string) preg_replace('#\s*\R\s*#', ' ', $semEco))
        );

        if ($motivo === '') {
            return 'O servidor de e-mail recusou ou não respondeu à mensagem.';
        }

        return 'Falha no envio SMTP: ' . $motivo;
    }

    /**
     * Remove qualquer segredo que possa aparecer num diagnóstico.
     *
     * O transporte não devolve a senha em texto puro, mas o valor configurado
     * e sua base64 (usada no AUTH) são removidos por precaução, para que o
     * motivo técnico possa ir inteiro para o log.
     */
    private function semSegredos(string $texto): string
    {
        $trocas = [];

        foreach (['email_smtp_pass', 'email_smtp_user'] as $chave) {
            $valor = $this->configuracoes->obter($chave);

            if ($valor !== null && $valor !== '') {
                $trocas[$valor]              = '[REMOVIDO]';
                $trocas[base64_encode($valor)] = '[REMOVIDO]';
            }
        }

        $texto = strtr($texto, $trocas);

        // Sequências longas em base64 são blocos de credencial ou certificado.
        $texto = (string) preg_replace('#[A-Za-z0-9+/=]{24,}#', '[REMOVIDO]', $texto);

        // Compacta a conversa SMTP em uma linha, para o log ficar legível.
        $texto = (string) preg_replace('#\s*\R\s*#', ' ', $texto);
        $texto = trim((string) preg_replace('#\s{2,}#', ' ', $texto));

        return mb_substr($texto, 0, 600);
    }

    /**
     * Motivo da última falha, já sem qualquer conteúdo de mensagem.
     */
    public function ultimoErro(): ?string
    {
        return $this->ultimoErro;
    }

    /**
     * Traduz as configurações da tela para o formato esperado pelo
     * `CodeIgniter\Email\Email`. Sem host SMTP configurado, cai no transporte
     * "mail" do próprio PHP.
     *
     * @return array<string, mixed>
     */
    private function configuracaoTransporte(): array
    {
        $base = config('Email');

        $host = $this->hostSmtp();

        return [
            'protocol'     => $host !== null ? 'smtp' : ($base->protocol ?: 'mail'),
            'SMTPHost'     => $host ?? '',
            'SMTPPort'     => $this->porta(),
            'SMTPUser'     => $this->configuracoes->obter('email_smtp_user') ?? '',
            'SMTPPass'     => $this->configuracoes->obter('email_smtp_pass') ?? '',
            'SMTPCrypto'   => $this->criptografia(),
            'SMTPAuth'     => $host !== null,
            'SMTPTimeout'  => $base->SMTPTimeout,
            'SMTPKeepAlive' => $base->SMTPKeepAlive,
            'mailType'     => 'html',
            'charset'      => $base->charset,
            'newline'      => $base->newline,
            'mailPath'     => $base->mailPath,
            'wordWrap'     => $base->wordWrap,
        ];
    }

    /**
     * Porta SMTP efetiva.
     *
     * A porta 587 é a padrão de "submissão com STARTTLS" e é a única que faz
     * sentido com a instalação mais comum (Gmail, Outlook, servidores
     * institucionais). Sem porta gravada, vale 587 — e não a porta 25 que o
     * `Config\Email` traz por omissão, que exigiria relay aberto.
     */
    private function porta(): int
    {
        $porta = (int) $this->configuracoes->obter('email_smtp_port', '587');

        return $porta > 0 ? $porta : 587;
    }

    /**
     * Criptografia efetiva do transporte SMTP.
     *
     * A porta 465 é TLS implícito (RFC 8314): o `ssl` do `CodeIgniter\Email` é
     * obrigatório nesse caso, e o STARTTLS seria um segundo upgrade sobre um
     * canal já criptografado.
     *
     * Nas demais portas vale a escolha da equipe. "Nenhuma" é uma escolha
     * legítima (relay interno sem TLS) e precisa ser respeitada: só quando a
     * chave nunca foi gravada é que o padrão seguro, 'tls', entra.
     */
    private function criptografia(): string
    {
        if ($this->porta() === 465) {
            return 'ssl';
        }

        $escolhida = $this->configuracoes->obterBruto('email_smtp_crypto');

        if ($escolhida === null) {
            return 'tls';
        }

        return in_array($escolhida, ['tls', 'ssl'], true) ? $escolhida : '';
    }

    /**
     * Host SMTP configurado, ou null quando o envio usa o transporte "mail".
     */
    private function hostSmtp(): ?string
    {
        $host = $this->configuracoes->obter('email_smtp_host');

        return ($host !== null && trim($host) !== '') ? trim($host) : null;
    }

    /**
     * Informa se o PHP em execução consegue criptografar a conexão.
     *
     * Sem a extensão openssl não existe transporte 'tls'/'ssl' e o
     * STARTTLS falha com "This stream does not support SSL/crypto" — erro que
     * aponta para a configuração do servidor, mas cuja causa é o ambiente.
     * Verificar antes evita mandar o operador caçar host/porta/senha à toa.
     */
    public function tlsSuportado(): bool
    {
        $transportes = stream_get_transports();

        return extension_loaded('openssl')
            && (in_array('tls', $transportes, true) || in_array('ssl', $transportes, true));
    }
}
