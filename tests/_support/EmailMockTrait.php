<?php

namespace Tests\Support;

use App\Models\ConfiguracaoModel;
use CodeIgniter\Events\Events;
use CodeIgniter\Test\Mock\MockEmail;
use Config\Services;

/**
 * Deixa o envio de e-mail seguro e observável nos testes.
 *
 * Sem isso, um fluxo que dispara convite tentaria falar com o SMTP de verdade
 * (ou com o mail() do PHP) e o cadastro inteiro seria desfeito por um erro de
 * rede — teste quebrando por motivo externo. Aqui o transporte é substituído
 * pelo MockEmail e o remetente é configurado, de modo que o fluxo se comporta
 * como em produção e o teste consegue ler o que foi enviado.
 */
trait EmailMockTrait
{
    protected MockEmail $emailMock;

    /** @var list<array<string, mixed>> */
    private array $emailsEnviados = [];

    /**
     * @param array<string, string> $configuracoes chaves extras de sistema_configuracoes
     */
    protected function mockarEmail(array $configuracoes = []): MockEmail
    {
        $this->emailMock        = new MockEmail(config('Email'));
        $this->emailsEnviados   = [];

        Services::injectMock('email', $this->emailMock);

        (new ConfiguracaoModel())->salvarCampos(array_merge([
            'email'                 => 'procuradoria@exemplo.test',
            'email_remetente_email' => 'procuradoria@exemplo.test',
            'email_remetente_nome'  => 'Procuradoria da Mulher',
        ], $configuracoes));

        // O MockEmail avisa cada envio pelo evento `email`.
        Events::on('email', function ($mensagem): void {
            $this->emailsEnviados[] = $mensagem;
        });

        return $this->emailMock;
    }

    /**
     * Faz o transporte recusar a mensagem, para exercitar o caminho de falha e
     * garantir que nada seja salvo pela metade.
     */
    protected function mockarEmailComFalha(): MockEmail
    {
        $this->emailMock->returnValue = false;

        return $this->emailMock;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function emailsEnviados(): array
    {
        return $this->emailsEnviados;
    }

    protected function ultimoEmailEnviado(): ?array
    {
        return $this->emailsEnviados === [] ? null : $this->emailsEnviados[array_key_last($this->emailsEnviados)];
    }
}
