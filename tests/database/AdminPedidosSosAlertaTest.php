<?php

use App\Models\MulherModel;
use App\Models\PedidoSosModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do alerta de novo pedido SOS no painel administrativo.
 *
 * @internal
 */
final class AdminPedidosSosAlertaTest extends CIUnitTestCase
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

    private function inserirPedido(array $m, string $dataHora, ?string $visualizadoEm = null): int
    {
        $dados = [
            'mulher_id' => $m['mulherId'],
            'data_hora' => $dataHora,
            'status'    => 'pendente',
            'latitude'  => -23.550520,
            'longitude' => -46.633309,
        ];

        if ($visualizadoEm !== null) {
            $dados['visualizado_em'] = $visualizadoEm;
        }

        $pedidoId = (new PedidoSosModel())->insert($dados);

        $this->assertIsInt($pedidoId);

        return $pedidoId;
    }

    private function jsonNovos(CodeIgniter\Test\TestResponse $result): object
    {
        $decoded = json_decode((string) $result->getJSON());

        $this->assertIsObject($decoded);

        return $decoded;
    }

    public function testEndpointRetornaApenasPedidosPendentesNaoVisualizados(): void
    {
        $m = $this->criarMulher('Ana Souza', 'ana.alerta@teste.com');
        $novo1 = $this->inserirPedido($m, '2026-09-06 08:00:00');
        $novo2 = $this->inserirPedido($m, '2026-09-06 09:00:00');
        $visualizado = $this->inserirPedido($m, '2026-09-06 10:00:00', '2026-09-06 10:30:00');

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos/novos');

        $result->assertStatus(200);
        $json = $this->jsonNovos($result);

        $idsNovos = array_column($json->novos, 'id');
        $this->assertContains($novo1, $idsNovos);
        $this->assertContains($novo2, $idsNovos);
        $this->assertNotContains($visualizado, $idsNovos);
        $this->assertSame(3, (int) $json->pendentes);
    }

    public function testEndpointRetornaDadosNecessariosParaOAlerta(): void
    {
        $m = $this->criarMulher('Maria Silva', 'maria.dados@teste.com');
        $this->inserirPedido($m, '2026-09-06 08:00:00');

        $this->logarComoAdmin();
        $result = $this->get('pedidos-sos/novos');
        $json = $this->jsonNovos($result);

        $this->assertCount(1, $json->novos);
        $pedido = $json->novos[0];
        $this->assertSame('Maria Silva', $pedido->mulher);
        $this->assertSame('2026-09-06 08:00:00', $pedido->data_hora);
        $this->assertSame(-23.550520, $pedido->latitude);
        $this->assertSame(-46.633309, $pedido->longitude);
        $this->assertObjectHasProperty('id', $pedido);
    }

    public function testAbrirDetalhesMarcaVisualizadoESaiDosNovos(): void
    {
        $m = $this->criarMulher('Joana Melo', 'joana.visto@teste.com');
        $pedidoId = $this->inserirPedido($m, '2026-09-06 08:00:00');

        $this->logarComoAdmin();

        $antes = $this->jsonNovos($this->get('pedidos-sos/novos'));
        $this->assertContains($pedidoId, array_column($antes->novos, 'id'));

        $detalhes = $this->get('pedidos-sos/' . $pedidoId);
        $detalhes->assertStatus(200);
        $this->assertStringContainsString('Pendente', $detalhes->getBody());

        $pedido = (new PedidoSosModel())->comMulher($pedidoId);
        $this->assertSame('pendente', $pedido['status']);
        $this->assertNotNull($pedido['visualizado_em']);

        $depois = $this->jsonNovos($this->get('pedidos-sos/novos'));
        $this->assertNotContains($pedidoId, array_column($depois->novos, 'id'));
    }

    public function testNovosOrdenadosMaisRecentesPrimeiro(): void
    {
        $m = $this->criarMulher('Bia Andrade', 'bia.alertordem@teste.com');
        $recente = $this->inserirPedido($m, '2026-09-06 09:30:00');
        $antigo = $this->inserirPedido($m, '2026-09-01 08:00:00');

        $this->logarComoAdmin();
        $json = $this->jsonNovos($this->get('pedidos-sos/novos'));

        $idsNovos = array_column($json->novos, 'id');
        $this->assertSame($recente, (int) $idsNovos[0]);
        $this->assertSame($antigo, (int) $idsNovos[1]);
    }

    public function testContadorDePendentesRefleteTodosOsPedidosPendentes(): void
    {
        $m = $this->criarMulher('Carla Reis', 'carla.contador@teste.com');
        $this->inserirPedido($m, '2026-09-06 08:00:00');
        $this->inserirPedido($m, '2026-09-06 09:00:00', '2026-09-06 09:30:00');
        $this->inserirPedido($m, '2026-09-06 10:00:00', '2026-09-06 10:30:00');

        $this->logarComoAdmin();
        $json = $this->jsonNovos($this->get('pedidos-sos/novos'));

        $this->assertCount(1, $json->novos);
        $this->assertSame(3, (int) $json->pendentes);
    }

    public function testMulherNaoAcessaEndpointDeNovos(): void
    {
        $m = $this->criarMulher('Duda Prado', 'duda.bloqueionovo@teste.com');
        $m['nome'] = 'Duda Prado';
        $m['email'] = 'duda.bloqueionovo@teste.com';

        $this->logarComoMulher($m['userId'], 'Duda Prado', 'duda.bloqueionovo@teste.com');
        $result = $this->get('pedidos-sos/novos');

        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('erro');
    }

    public function testFuncionarioNaoAcessaEndpointDeNovos(): void
    {
        $this->logarComoFuncionario();
        $result = $this->get('pedidos-sos/novos');

        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('erro');
    }

    public function testNaoAutenticadoNaoAcessaEndpointDeNovos(): void
    {
        $result = $this->get('pedidos-sos/novos');
        $result->assertRedirectTo('/login');
    }
}