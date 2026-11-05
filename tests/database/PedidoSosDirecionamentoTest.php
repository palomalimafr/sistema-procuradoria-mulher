<?php

use App\Models\AtendimentoModel;
use App\Models\MulherModel;
use App\Models\PedidoSosModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do direcionamento de um Pedido SOS para um Atendimento.
 *
 * @internal
 */
final class PedidoSosDirecionamentoTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    private function criarMulher(string $nome, string $email): array
    {
        $userId = (new UserModel())->insert([
            'nome'   => $nome,
            'email'  => $email,
            'senha'  => 'senha123',
            'perfil' => 'mulher',
            'ativo'  => 1,
        ]);

        $this->assertIsInt($userId);

        $mulherId = (new MulherModel())->insert([
            'user_id'       => $userId,
            'nome_completo' => $nome,
            'email'         => $email,
        ]);

        $this->assertIsInt($mulherId);

        return ['userId' => $userId, 'mulherId' => $mulherId, 'nome' => $nome, 'email' => $email];
    }

    private function criarPedido(int $mulherId): int
    {
        $id = (new PedidoSosModel())->insert([
            'mulher_id' => $mulherId,
            'data_hora' => date('Y-m-d H:i:s'),
            'status'    => 'pendente',
            'latitude'  => -23.550520,
            'longitude' => -46.633309,
        ]);

        $this->assertIsInt($id);

        return $id;
    }

    private function logarComoAdmin(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Admin Teste',
            'usuario_email' => 'admin@teste.com',
            'perfil'        => 'admin',
        ]);
    }

    private function logarComoFuncionario(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Funcionário',
            'usuario_email' => 'func@teste.com',
            'perfil'        => 'funcionario',
        ]);
    }

    private function logarComoMulher(array $m): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => $m['userId'],
            'usuario_nome'  => $m['nome'],
            'usuario_email' => $m['email'],
            'perfil'        => 'mulher',
        ]);
    }

    private function dadosAtendimento(int $mulherId, array $extra = []): array
    {
        return array_merge([
            'mulher_id'          => (string) $mulherId,
            'data_hora'          => date('Y-m-d\TH:i'),
            'forma_atendimento'  => 'presencial',
            'origem'             => 'demanda_espontanea',
            'prioridade'         => 'alta',
            'situacao'           => 'novo',
            'unidade_setor'      => 'Procuradoria',
            'motivo_atendimento' => 'Acionamento via SOS',
            'descricao_caso'     => 'Relato recebido pelo botão SOS.',
        ], $extra);
    }

    private function criarAtendimentoApartirDoSos(array $m, int $pedidoId): int
    {
        $this->logarComoAdmin();
        $this->post('atendimentos', $this->dadosAtendimento((int) $m['mulherId'], [
            'pedido_sos_id' => (string) $pedidoId,
        ]));

        $atendimento = (new AtendimentoModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($atendimento);

        return (int) $atendimento['id'];
    }

    private function assertRedirectContem($result, string $trecho): void
    {
        $result->assertRedirect();
        $url = $result->getRedirectUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString($trecho, (string) $url);
    }

    public function testDetalhesDePedidoPendenteMostraAcaoDeDirecionar(): void
    {
        $m        = $this->criarMulher('Ana Souza', 'ana.dir@teste.com');
        $pedidoId = $this->criarPedido($m['mulherId']);

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos/' . $pedidoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Direcionar para Atendimento', $body);
        $this->assertStringContainsString('pedidos-sos/' . $pedidoId . '/direcionar', $body);
    }

    public function testAdminDirecionaParaFormularioComMulherPreSelecionada(): void
    {
        $m        = $this->criarMulher('Bianca Lima', 'bianca.dir@teste.com');
        $pedidoId = $this->criarPedido($m['mulherId']);

        $this->logarComoAdmin();
        $result = $this->post('pedidos-sos/' . $pedidoId . '/direcionar');

        $this->assertRedirectContem($result, '/atendimentos/novo');
        $this->assertRedirectContem($result, 'mulher_id=' . $m['mulherId']);
        $this->assertRedirectContem($result, 'pedido_sos_id=' . $pedidoId);

        $form = $this->get('atendimentos/novo?mulher_id=' . $m['mulherId'] . '&pedido_sos_id=' . $pedidoId);
        $form->assertStatus(200);

        $body = $form->getBody();
        $this->assertStringContainsString('Bianca Lima', $body);
        $this->assertStringContainsString('name="pedido_sos_id"', $body);
        $this->assertStringContainsString('Pedido SOS #' . $pedidoId, $body);
    }

    public function testCriarAtendimentoApartirDoSosGravaVinculoEMarcaPedido(): void
    {
        $m        = $this->criarMulher('Camila Rocha', 'camila.dir@teste.com');
        $pedidoId = $this->criarPedido($m['mulherId']);

        $this->logarComoAdmin();
        $result = $this->post('atendimentos', $this->dadosAtendimento((int) $m['mulherId'], [
            'pedido_sos_id' => (string) $pedidoId,
        ]));

        $result->assertRedirectTo('/atendimentos/fila');

        $atendimento = (new AtendimentoModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($atendimento);
        $this->assertSame($pedidoId, (int) $atendimento['pedido_sos_id']);
        $this->assertSame('novo', $atendimento['situacao']);

        $this->assertSame('direcionado', (new PedidoSosModel())->find($pedidoId)['status']);
    }

    public function testSosDirecionadoDeixaDeSerPendente(): void
    {
        $m        = $this->criarMulher('Diana Alves', 'diana.dir@teste.com');
        $pedidoId = $this->criarPedido($m['mulherId']);

        $this->assertSame(1, (new PedidoSosModel())->totalPendentes());

        $this->criarAtendimentoApartirDoSos($m, $pedidoId);

        $this->assertSame(0, (new PedidoSosModel())->totalPendentes());
        $this->assertSame('direcionado', (new PedidoSosModel())->find($pedidoId)['status']);

        $result = $this->get('pedidos-sos/novos');
        $result->assertStatus(200);

        $json = json_decode((string) $result->getJSON(), true);
        $this->assertSame(0, (int) $json['pendentes']);
        $this->assertSame([], $json['novos']);
    }

    public function testDetalhesDePedidoDirecionadoMostraVinculoEProtocolo(): void
    {
        $m          = $this->criarMulher('Elena Nunes', 'elena.dir@teste.com');
        $pedidoId   = $this->criarPedido($m['mulherId']);
        $atendimentoId = $this->criarAtendimentoApartirDoSos($m, $pedidoId);

        $atendimento = (new AtendimentoModel())->find($atendimentoId);

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos/' . $pedidoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Pedido direcionado', $body);
        $this->assertStringContainsString($atendimento['protocolo'], $body);
        $this->assertStringContainsString('atendimentos/' . $atendimentoId, $body);
        $this->assertStringNotContainsString('Direcionar para Atendimento', $body);
    }

    public function testNaoCriaAtendimentoDuplicadoSeMulherJaTemAberto(): void
    {
        $m         = $this->criarMulher('Fátima Reis', 'fatima.dir@teste.com');
        $pedido1   = $this->criarPedido($m['mulherId']);
        $this->criarAtendimentoApartirDoSos($m, $pedido1);

        $pedido2 = $this->criarPedido($m['mulherId']);

        $this->logarComoAdmin();
        $result = $this->post('pedidos-sos/' . $pedido2 . '/direcionar');

        $this->assertRedirectContem($result, '/pedidos-sos/' . $pedido2);
        $result->assertSessionHas('erro');

        $this->assertSame(1, (new AtendimentoModel())->where('mulher_id', $m['mulherId'])->countAllResults());
        $this->assertSame('pendente', (new PedidoSosModel())->find($pedido2)['status']);
    }

    public function testFormularioBloqueiaQuandoMulherJaTemAtendimentoAberto(): void
    {
        $m       = $this->criarMulher('Gisele Prado', 'gisele.dir@teste.com');
        $pedido1 = $this->criarPedido($m['mulherId']);
        $this->criarAtendimentoApartirDoSos($m, $pedido1);

        $pedido2 = $this->criarPedido($m['mulherId']);

        $this->logarComoAdmin();
        $result = $this->get('atendimentos/novo?mulher_id=' . $m['mulherId'] . '&pedido_sos_id=' . $pedido2);

        $this->assertRedirectContem($result, '/pedidos-sos/' . $pedido2);
        $result->assertSessionHas('erro');
    }

    public function testDetalhesDePedidoPendenteComAtendimentoAbertoMostraAviso(): void
    {
        $m         = $this->criarMulher('Helena Castro', 'helena.dir@teste.com');
        $pedido1   = $this->criarPedido($m['mulherId']);
        $this->criarAtendimentoApartirDoSos($m, $pedido1);

        $pedido2 = $this->criarPedido($m['mulherId']);

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos/' . $pedido2);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Atendimento em andamento', $body);
        $this->assertStringContainsString('Acessar atendimento', $body);
        $this->assertStringNotContainsString('pedidos-sos/' . $pedido2 . '/direcionar', $body);
    }

    public function testDirecionarPedidoJaDirecionadoRedirecionaParaAtendimento(): void
    {
        $m             = $this->criarMulher('Iara Melo', 'iara.dir@teste.com');
        $pedidoId      = $this->criarPedido($m['mulherId']);
        $atendimentoId = $this->criarAtendimentoApartirDoSos($m, $pedidoId);

        $this->logarComoAdmin();
        $result = $this->post('pedidos-sos/' . $pedidoId . '/direcionar');

        $this->assertRedirectContem($result, '/atendimentos/' . $atendimentoId);
        $result->assertSessionHas('sucesso');
    }

    public function testPedidoInexistenteRedirecionaParaLista(): void
    {
        $this->logarComoAdmin();
        $result = $this->post('pedidos-sos/99999/direcionar');

        $this->assertRedirectContem($result, '/pedidos-sos');
        $result->assertSessionHas('erro');
    }

    public function testFuncionarioNaoPodeDirecionarPedido(): void
    {
        $m        = $this->criarMulher('Joana Prado', 'joana.dir@teste.com');
        $pedidoId = $this->criarPedido($m['mulherId']);

        $this->logarComoFuncionario();
        $result = $this->post('pedidos-sos/' . $pedidoId . '/direcionar');

        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('erro');
        $this->assertSame('pendente', (new PedidoSosModel())->find($pedidoId)['status']);
    }

    public function testMulherNaoPodeDirecionarPedido(): void
    {
        $m        = $this->criarMulher('Karla Neves', 'karla.dir@teste.com');
        $pedidoId = $this->criarPedido($m['mulherId']);

        $this->logarComoMulher($m);
        $result = $this->post('pedidos-sos/' . $pedidoId . '/direcionar');

        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('erro');
        $this->assertSame('pendente', (new PedidoSosModel())->find($pedidoId)['status']);
    }

    public function testAtendimentoManualSemSosNaoCriaVinculo(): void
    {
        $m        = $this->criarMulher('Lara Fontes', 'lara.dir@teste.com');
        $pedidoId = $this->criarPedido($m['mulherId']);

        $this->logarComoAdmin();
        $result = $this->post('atendimentos', $this->dadosAtendimento((int) $m['mulherId']));
        $result->assertRedirectTo('/atendimentos/fila');

        $atendimento = (new AtendimentoModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($atendimento);
        $this->assertNull($atendimento['pedido_sos_id']);
        $this->assertSame('pendente', (new PedidoSosModel())->find($pedidoId)['status']);
    }
}
