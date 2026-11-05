<?php

use App\Models\MulherModel;
use App\Models\PedidoSosModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes da área administrativa dos Pedidos SOS.
 *
 * @internal
 */
final class AdminPedidosSosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    private const SP_LAT = '-23.550520';
    private const SP_LNG = '-46.633309';

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

        return ['userId' => $userId, 'mulherId' => $mulherId];
    }

    private function logarComoMulher(int $userId, string $nome, string $email): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => $userId,
            'usuario_nome'  => $nome,
            'usuario_email' => $email,
            'perfil'        => 'mulher',
        ]);
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

    private function criarPedidoComoMulher(array $m, float $lat = -23.550520, float $lng = -46.633309): int
    {
        $this->logarComoMulher($m['userId'], $m['nome'] ?? 'Mulher Teste', $m['email'] ?? 'm@teste.com');
        $this->post('pedidos-sos/criar', [
            'latitude'  => (string) $lat,
            'longitude' => (string) $lng,
        ]);

        $pedido = (new PedidoSosModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($pedido);

        return (int) $pedido['id'];
    }

    public function testAdminListaPedidosSosComDadosDaMulher(): void
    {
        $m = $this->criarMulher('Maria Silva', 'maria.lista@teste.com');
        $m['nome'] = 'Maria Silva';
        $m['email'] = 'maria.lista@teste.com';
        $pedidoId = $this->criarPedidoComoMulher($m);

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('#', $body);
        $this->assertStringContainsString('Maria Silva', $body);
        $this->assertStringContainsString('Pendente', $body);
        $this->assertStringContainsString('Ver localiza', $body);
        $this->assertStringContainsString(date('d/m/Y'), $body);
        $this->assertStringContainsString('#' . $pedidoId, $body);
    }

    public function testAdminVeDetalhesDoPedidoComLocalizacao(): void
    {
        $m = $this->criarMulher('Joana Melo', 'joana.detalhe@teste.com');
        $m['nome'] = 'Joana Melo';
        $m['email'] = 'joana.detalhe@teste.com';
        $pedidoId = $this->criarPedidoComoMulher($m, -22.906847, -43.172897);

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos/' . $pedidoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Pedido SOS #' . $pedidoId, $body);
        $this->assertStringContainsString('Joana Melo', $body);
        $this->assertStringContainsString('Pendente', $body);
        $this->assertStringContainsString('Latitude', $body);
        $this->assertStringContainsString('Longitude', $body);
        $this->assertStringContainsString('-22.906847', $body);
        $this->assertStringContainsString('-43.172897', $body);
    }

    public function testPedidosSaoOrdenadosMaisRecentesPrimeiro(): void
    {
        $m = $this->criarMulher('Bia Andrade', 'bia.ordem@teste.com');

        $model = new PedidoSosModel();
        $antigo = $model->insert([
            'mulher_id' => $m['mulherId'],
            'data_hora' => '2026-09-01 08:00:00',
            'status'    => 'pendente',
            'latitude'  => -23.1,
            'longitude' => -46.1,
        ]);
        $recente = $model->insert([
            'mulher_id' => $m['mulherId'],
            'data_hora' => '2026-09-06 09:30:00',
            'status'    => 'pendente',
            'latitude'  => -23.2,
            'longitude' => -46.2,
        ]);
        $this->assertIsInt($antigo);
        $this->assertIsInt($recente);

        $listagem = $model->listarComMulher(10);
        $this->assertSame($recente, (int) $listagem[0]['id']);
        $this->assertSame($antigo, (int) $listagem[1]['id']);

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos');
        $body = $result->getBody();
        $cellAntigo = strpos($body, '">#' . $antigo . '</td>');
        $cellRecente = strpos($body, '">#' . $recente . '</td>');
        $this->assertLessThan($cellAntigo, $cellRecente);
    }

    public function testDashboardAdminMostraContagemDePendentes(): void
    {
        $m = $this->criarMulher('Carla Reis', 'carla.dash@teste.com');
        $m['nome'] = 'Carla Reis';
        $m['email'] = 'carla.dash@teste.com';
        $this->criarPedidoComoMulher($m);
        $this->criarPedidoComoMulher($m, 0.1, 0.2);

        $this->logarComoAdmin();
        $result = $this->get('dashboard');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Pedidos SOS', $body);

        // O cartao SOS do painel mostra a contagem real de pendentes e
        // encaminha para a fila de pedidos.
        $pos = strpos($body, 'Pedidos SOS sem atendimento');
        $this->assertNotFalse($pos, 'O cartao SOS deve existir para o administrador.');
        $this->assertMatchesRegularExpression(
            '/Pedidos SOS sem atendimento.*?leading-5">\s*2\s*</s',
            substr($body, $pos, 900),
            'O cartao SOS deve exibir os 2 pedidos pendentes criados.'
        );
        $this->assertStringContainsString('pedidos-sos', $body);
    }

    public function testMulherNaoAcessaPedidosSos(): void
    {
        $m = $this->criarMulher('Duda Prado', 'duda.bloqueio@teste.com');
        $m['nome'] = 'Duda Prado';
        $m['email'] = 'duda.bloqueio@teste.com';
        $pedidoId = $this->criarPedidoComoMulher($m);

        $this->logarComoMulher($m['userId'], 'Duda Prado', 'duda.bloqueio@teste.com');
        $result = $this->get('pedidos-sos');
        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('erro');

        $result2 = $this->get('pedidos-sos/' . $pedidoId);
        $result2->assertRedirectTo('/dashboard');
    }

    public function testFuncionarioNaoAcessaPedidosSos(): void
    {
        $this->logarComoFuncionario();
        $result = $this->get('pedidos-sos');
        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('erro');
    }

    public function testNaoAutenticadoEEnviadoAoLogin(): void
    {
        $result = $this->get('pedidos-sos');
        $result->assertRedirectTo('/login');
    }

    public function testPedidoInexistenteRedirecionaParaLista(): void
    {
        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos/99999');
        $result->assertRedirectTo('/pedidos-sos');
        $result->assertSessionHas('erro');
    }
}
