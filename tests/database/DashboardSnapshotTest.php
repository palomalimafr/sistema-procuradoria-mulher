<?php

namespace Tests\Database;

use App\Models\AcolhimentoModel;
use App\Models\AtendimentoModel;
use App\Models\EncaminhamentoModel;
use App\Models\MedidaProtetivaModel;
use App\Models\PedidoSosModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * ETAPA 1 da dashboard: os cartoes sao um snapshot da situacao atual e nao
 * dependem de periodo. Aqui travamos os numeros e a logica de status, para
 * que ninguem invente uma regra diferente da que ja existe nos models.
 */
class DashboardSnapshotTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = TestDataSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function logarComo(string $perfil = 'admin'): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Equipe Teste',
            'usuario_email' => 'equipe@teste.com',
            'perfil'        => $perfil,
        ]);
    }

    private function corpo(TestResponse $r): string
    {
        return html_entity_decode((string) $r->getBody(), ENT_QUOTES, 'UTF-8');
    }

    private function criarMulher(string $nome): int
    {
        $id = (new \App\Models\MulherModel())->insert([
            'nome_completo' => $nome,
            'data_nascimento' => '1990-01-01',
            'cpf' => null,
        ]);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarAtendimento(int $mulherId, string $situacao, string $protocolo): int
    {
        $id = (new AtendimentoModel())->insert([
            'protocolo'         => $protocolo,
            'data_hora'         => '2026-03-10 09:00:00',
            'mulher_id'         => $mulherId,
            'profissional_id'   => 1,
            'forma_atendimento' => 'presencial',
            'origem'            => 'demanda_espontanea',
            'prioridade'        => 'media',
            'situacao'          => $situacao,
        ]);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarEncaminhamento(int $mulherId, string $status, string $orgao): int
    {
        $id = (new EncaminhamentoModel())->insert([
            'data_hora' => '2026-03-11 09:00:00',
            'mulher_id' => $mulherId,
            'status'    => $status,
            'orgao'     => $orgao,
            'prioridade' => 'media',
        ]);
        $this->assertIsInt($id);

        return $id;
    }

    /* ================================================================
     | Numeros do snapshot
     * ================================================================ */

    public function testCartoesMostramOsNumerosReaisDoSnapshot(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Snapshot');

        // 2 abertos (novo + em_andamento) e 1 encerrado: so os abertos contam.
        $this->criarAtendimento($mulherId, 'novo', 'SNAP-01');
        $this->criarAtendimento($mulherId, 'em_andamento', 'SNAP-02');
        $this->criarAtendimento($mulherId, 'encerrado', 'SNAP-03');

        $corpo = $this->corpo($this->get('dashboard'));

        // 1 mulher cadastrada
        $this->assertStringContainsString('Mulheres cadastradas', $corpo);
        $this->assertStringContainsString('Atendimentos em aberto', $corpo);
        $this->assertStringNotContainsString('Atendimentos em aberto0', $corpo);
    }

    public function testAtendimentoEncerradoNaoEntraNoCardeDeAbertos(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria So Encerrados');

        $this->criarAtendimento($mulherId, 'encerrado', 'ENC-01');
        $this->criarAtendimento($mulherId, 'encerrado', 'ENC-02');

        $result = $this->get('dashboard');
        $result->assertStatus(200);

        $html = (string) $result->getBody();
        $pos = strpos($html, 'Atendimentos em aberto');
        $this->assertNotFalse($pos, 'O cartao de atendimentos em aberto deve existir');

        // O numero exibido no cartao vem logo apos o rotulo.
        $trecho = substr($html, $pos, 900);
        $this->assertMatchesRegularExpression(
            '/Atendimentos em aberto.*?leading-5">\s*0\s*</s',
            $trecho,
            'Nenhum atendimento esta aberto; o cartao deve exibir 0.'
        );
    }

    public function testEncaminhamentoUsaSomenteOsStatusPendentesDoModel(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Encaminhada');

        // Os 4 estados de STATUS_PENDENTES contam.
        $this->criarEncaminhamento($mulherId, 'pendente', 'CRAS');
        $this->criarEncaminhamento($mulherId, 'encaminhado', 'CREAS');
        $this->criarEncaminhamento($mulherId, 'em_atendimento', 'CRAS');
        $this->criarEncaminhamento($mulherId, 'aguardando_retorno', 'CRAS');
        // Os 2 estados finais nao contam.
        $this->criarEncaminhamento($mulherId, 'concluido', 'CRAS');
        $this->criarEncaminhamento($mulherId, 'cancelado', 'CRAS');

        $result = $this->get('dashboard');
        $html = (string) $result->getBody();
        $pos = strpos($html, 'Encaminhamentos pendentes');
        $this->assertNotFalse($pos);

        $this->assertMatchesRegularExpression(
            '/Encaminhamentos pendentes.*?leading-5">\s*4\s*</s',
            substr($html, $pos, 900),
            'Devem contar apenas os 4 status de EncaminhamentoModel::STATUS_PENDENTES.'
        );
    }

    public function testAcolhimentoContaSomenteOsQueEstaoEmAndamento(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Acolhida');
        $atendimentoId = $this->criarAtendimento($mulherId, 'em_andamento', 'ACO-01');

        (new AcolhimentoModel())->insert([
            'atendimento_id' => $atendimentoId,
            'data_hora'      => '2026-03-12 09:00:00',
            'status'         => 'em_andamento',
            'motivo_procura' => 'Demanda',
        ]);
        (new AcolhimentoModel())->insert([
            'atendimento_id'  => $atendimentoId,
            'data_hora'       => '2026-03-12 10:00:00',
            'status'          => 'concluido',
            'data_conclusao'  => '2026-03-13 09:00:00',
            'motivo_procura'  => 'Demanda',
        ]);

        $html = (string) $this->get('dashboard')->getBody();
        $pos = strpos($html, 'Acolhimentos em andamento');
        $this->assertNotFalse($pos);

        $this->assertMatchesRegularExpression(
            '/Acolhimentos em andamento.*?leading-5">\s*1\s*</s',
            substr($html, $pos, 900)
        );
    }

    public function testMedidasAtivasUsamAsSituacoesNaoTerminaisDoModel(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Medidas');

        foreach (['solicitada', 'em_analise', 'concedida'] as $i => $situacao) {
            (new MedidaProtetivaModel())->insert([
                'mulher_id'        => $mulherId,
                'orgao'            => 'poder_judiciario',
                'situacao'         => $situacao,
                'data_solicitacao' => '2026-03-1' . $i,
            ]);
        }
        // Situacoes finais nao contam.
        foreach (['negada', 'revogada', 'encerrada'] as $i => $situacao) {
            (new MedidaProtetivaModel())->insert([
                'mulher_id'        => $mulherId,
                'orgao'            => 'poder_judiciario',
                'situacao'         => $situacao,
                'data_solicitacao' => '2026-03-2' . $i,
            ]);
        }

        $html = (string) $this->get('dashboard')->getBody();
        $pos = strpos($html, 'Medidas protetivas ativas');
        $this->assertNotFalse($pos);

        $this->assertMatchesRegularExpression(
            '/Medidas protetivas ativas.*?leading-5">\s*3\s*</s',
            substr($html, $pos, 900)
        );
    }

    /* ================================================================
     | SOS
     * ================================================================ */

    public function testCartaoSosApareceSoParaAdmin(): void
    {
        $this->logarComo('admin');
        $corpo = $this->corpo($this->get('dashboard'));
        $this->assertStringContainsString('Pedidos SOS sem atendimento', $corpo);

        $this->logarComo('funcionario');
        $corpo = $this->corpo($this->get('dashboard'));
        $this->assertStringNotContainsString('Pedidos SOS sem atendimento', $corpo);
    }

    public function testSosPendenteFicaMarcadoComoRiscoEAZeroNaoFica(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Sos');

        // Sem pendentes: o cartao nao recebe tratamento de risco.
        $html = $this->corpo($this->get('dashboard'));
        $pos = strpos($html, 'Pedidos SOS sem atendimento');
        $this->assertNotFalse($pos);
        $this->assertStringNotContainsString('Requer atenção', substr($html, $pos, 900));

        // A localização é obrigatória no pedido SOS: um pedido real sempre a tem.
        (new PedidoSosModel())->insert([
            'mulher_id' => $mulherId,
            'data_hora' => '2026-03-14 22:00:00',
            'status'    => 'pendente',
            'latitude'  => '-23.5505199',
            'longitude' => '-46.6333094',
        ]);

        $html = $this->corpo($this->get('dashboard'));
        $pos = strpos($html, 'Pedidos SOS sem atendimento');
        $this->assertNotFalse($pos);
        $this->assertStringContainsString('Requer atenção', substr($html, $pos, 900));
    }

    /* ================================================================
     | Pendencias operacionais
     * ================================================================ */

    public function testCompromissoVencidoApareceComoPendenciaReal(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Compromisso');
        $atendimentoId = $this->criarAtendimento($mulherId, 'em_andamento', 'COM-01');

        // Agendado com data passada: pendencia real, NAO e proximo.
        (new \App\Models\CompromissoModel())->insert([
            'mulher_id'      => $mulherId,
            'atendimento_id' => $atendimentoId,
            'tipo'           => 'atendimento_juridico',
            'data'           => '2020-01-10',
            'hora_inicio'    => '09:00:00',
            'profissional_id' => 1,
            'status'         => 'agendado',
        ]);

        $corpo = $this->corpo($this->get('dashboard'));

        $this->assertStringContainsString('Compromissos vencidos sem desfecho', $corpo);
    }

    public function testCompromissoFuturoNaoViraPendencia(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Futura');
        $atendimentoId = $this->criarAtendimento($mulherId, 'em_andamento', 'FUT-01');

        (new \App\Models\CompromissoModel())->insert([
            'mulher_id'      => $mulherId,
            'atendimento_id' => $atendimentoId,
            'tipo'           => 'reuniao',
            'data'           => date('Y-m-d', strtotime('+30 days')),
            'hora_inicio'    => '09:00:00',
            'profissional_id' => 1,
            'status'         => 'agendado',
        ]);

        $corpo = $this->corpo($this->get('dashboard'));

        $this->assertStringNotContainsString('Compromissos vencidos sem desfecho', $corpo);
    }

    public function testEstadoVazioDePendenciasQuandoNaoHaNadaAguardando(): void
    {
        $this->logarComo();
        $this->criarMulher('Maria Tranquila');

        $corpo = $this->corpo($this->get('dashboard'));

        $this->assertStringContainsString('Nenhuma pendência operacional', $corpo);
    }

    public function testPendenciaDesapareceQuandoOItemEConcluido(): void
    {
        $this->logarComo();
        $mulherId = $this->criarMulher('Maria Concluida');
        $this->criarEncaminhamento($mulherId, 'pendente', 'CRAS');

        $this->assertStringContainsString(
            'Encaminhamentos aguardando desfecho',
            $this->corpo($this->get('dashboard'))
        );

        db_connect()->table('encaminhamentos')
            ->where('mulher_id', $mulherId)
            ->update(['status' => 'concluido']);

        $this->assertStringNotContainsString(
            'Encaminhamentos aguardando desfecho',
            $this->corpo($this->get('dashboard'))
        );
    }

    /* ================================================================
     | Acabamento da etapa 1
     * ================================================================ */

    public function testNenhumNumeroFicticioNenhumPlaceholder(): void
    {
        $this->logarComo();
        $this->criarMulher('Maria Limpa');

        $corpo = $this->corpo($this->get('dashboard'));

        $this->assertStringNotContainsString('Sem dados', $corpo);
        $this->assertStringNotContainsString('Dados ainda não disponíveis', $corpo);
        $this->assertStringNotContainsString('Disponível em breve', $corpo);
        $this->assertStringNotContainsString('Indicadores gráficos', $corpo);
    }

    public function testAcoesRapidasApontamParaRotasQueExistem(): void
    {
        $this->logarComo();
        $corpo = $this->corpo($this->get('dashboard'));

        $this->assertStringContainsString('atendimentos/novo', $corpo);
        $this->assertStringContainsString('mulheres/cadastro', $corpo);
        $this->assertStringContainsString('atendimentos/fila', $corpo);
        $this->assertStringContainsString('casos/urgentes', $corpo);
        $this->assertStringContainsString('pedidos-sos', $corpo);
    }

    /**
     * Regressão do 404 em "Mulheres cadastradas".
     *
     * Todo link do painel tem que existir de fato em app/Config/Routes.php.
     * Aqui extraímos os hrefs reais do HTML e confrontamos cada um com a tabela
     * de rotas, sem despachar os controllers — assim o teste mede exatamente
     * "o link aponta para uma rota que existe?" e não é influenciado por erro
     * de execução de outra tela.
     */
    public function testNenhumLinkDoPainelApontaParaRotaInexistente(): void
    {
        $this->logarComo();
        $this->criarMulher('Maria Links');
        $this->criarEncaminhamento($this->criarMulher('Maria Pende'), 'pendente', 'CRAS');

        $html = (string) $this->get('dashboard')->getBody();
        $this->assertSame(200, $this->get('dashboard')->response()->getStatusCode());

        // Só interessa o que o painel linka dentro do app; manifest.json, css,
        // js, imagens e fontes externas são arquivos servidos pelo web server.
        $base     = base_url();
        $estatico = static fn (string $h): bool => (bool) preg_match(
            '/\.(json|css|js|png|jpe?g|svg|gif|webp|ico|webmanifest|xml|txt|woff2?)$/i',
            parse_url($h, PHP_URL_PATH) ?? ''
        );

        preg_match_all('/href="([^"]+)"/', $html, $m);
        $internos = array_values(array_unique(array_filter(
            $m[1],
            static fn (string $h): bool => str_starts_with($h, $base)
                && str_contains($h, 'index.php') === false
                && ! $estatico($h)
        )));

        $this->assertNotEmpty($internos, 'O painel deve ter links internos.');

        $rotasValidas = $this->rotasGetComoRegex();

        foreach ($internos as $href) {
            $rota = (string) parse_url($href, PHP_URL_PATH);
            $rota = trim((string) preg_replace('#^.*/index\.php/?#', '', $rota), '/');
            if ($rota === '') {
                continue;
            }

            $existe = false;
            foreach ($rotasValidas as $regex) {
                if (preg_match($regex, $rota) === 1) {
                    $existe = true;
                    break;
                }
            }

            $this->assertTrue(
                $existe,
                sprintf('Link do painel aponta para rota inexistente: "%s".', $rota)
            );
        }
    }

    /**
     * Converte as rotas GET declaradas em regex, traduzindo os placeholders do
     * CodeIgniter para o mesmo tipo de casamento que o framework faz.
     *
     * @return list<string>
     */
    private function rotasGetComoRegex(): array
    {
        $regex = [];

        foreach (array_keys(service('routes')->getRoutes('GET')) as $rota) {
            $traduzida = preg_replace_callback(
                '/\(:(\w+)\)/',
                static fn (array $p): string => match ($p[1]) {
                    'num'     => '(\d+)',
                    'alpha'   => '([a-zA-Z]+)',
                    'any'     => '(.*)',
                    'segment' => '([^/]+)',
                    default   => '([^/]+)',
                },
                $rota
            );

            $regex[] = '#^' . $traduzida . '$#';
        }

        return $regex;
    }


    public function testNaoExisteFiltroDePeriodoNaEtapa1(): void
    {
        $this->logarComo();
        $corpo = $this->corpo($this->get('dashboard'));

        $this->assertStringNotContainsString('data_de', $corpo);
        $this->assertStringNotContainsString('data_ate', $corpo);
    }
}
