<?php

namespace Tests\Support\Libraries;

use CodeIgniter\Test\Mock\MockEmail;

/**
 * Transporte SMTP recusado, com a conversa que o servidor de verdade teria
 * devolvido.
 *
 * O MockEmail comum só sabe devolver false: com ele, qualquer diagnóstico do
 * EmailServico fica vazio e a extração do motivo real da recusa nunca é
 * exercitada. Aqui a recusa vem acompanhada do diálogo do servidor (EHLO,
 * STARTTLS e o erro 535 do Gmail), que é exatamente o texto que o operador
 * precisa ver no log.
 */
class TransporteRecusadoEmail extends MockEmail
{
    /** @var string[] Diálogo que o servidor teria devolvido. */
    private array $conversaSMTP = [
        '<pre>hello: 250-smtp.gmail.com at your service, [2804:e4c:3e01:19e7:6900:b26:e044:196c]</pre>',
        '<pre>starttls: 220 2.0.0 Ready to start TLS</pre>',
        'Failed to authenticate password. Error: 535-5.7.8 Username and Password not accepted.'
        . ' For more information, go to 535 5.7.8 https://support.google.com/mail/?p=BadCredentials - gsmtp',
    ];

    public function send($autoClear = true)
    {
        $enviado = parent::send($autoClear);

        if (! $enviado) {
            // A conversa entra depois do clear() que initialize()/send() fazem,
            // que é a mesma ordem em que o Email real a monta.
            foreach ($this->conversaSMTP as $linha) {
                $this->debugMessage[]    = $linha;
                $this->debugMessageRaw[] = strip_tags($linha);
            }
        }

        return $enviado;
    }
}
