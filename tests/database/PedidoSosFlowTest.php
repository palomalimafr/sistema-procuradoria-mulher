<?php

use App\Models\MulherModel;
use App\Models\PedidoSosModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do fluxo de Pedido SOS da mulher.
 *
 * @internal
 */
final class PedidoSosFlowTest extends CIUnitTestCase
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

    private const SP_LAT = '-23.550520';
    private const SP_LNG = '-46.633309';
    private const RJ_LAT = '-22.906847';
    private const RJ_LNG = '-43.172897';

    public function testMulherCriaPedidoSosComLocalizacao(): void
    {
        $m = $this->criarMulher('Ana Souza', 'ana@teste.com');
        $this->logarComoMulher($m['userId'], 'Ana Souza', 'ana@teste.com');

        $result = $this->post('pedidos-sos/criar', [
            'latitude'  => self::SP_LAT,
            'longitude' => self::SP_LNG,
        ]);

        $result->assertRedirectTo('/mulher/dashboard');
        $result->assertSessionHas('sos_sucesso');

        $pedido = (new PedidoSosModel())->first();
        $this->assertNotNull($pedido);
        $this->assertSame($m['mulherId'], (int) $pedido['mulher_id']);
        $this->assertSame('pendente', $pedido['status']);
        $this->assertNotNull($pedido['data_hora']);
        $this->assertNotNull($pedido['created_at']);
        $this->assertSame((float) self::SP_LAT, (float) $pedido['latitude']);
        $this->assertSame((float) self::SP_LNG, (float) $pedido['longitude']);
    }

    public function testPedidoFicaAssociadoAMulherCorreta(): void
    {
        $m1 = $this->criarMulher('Bianca Lima', 'bianca@teste.com');
        $m2 = $this->criarMulher('Camila Rocha', 'camila@teste.com');

        $this->logarComoMulher($m1['userId'], 'Bianca Lima', 'bianca@teste.com');
        $this->post('pedidos-sos/criar', ['latitude' => self::SP_LAT, 'longitude' => self::SP_LNG]);

        $this->logarComoMulher($m2['userId'], 'Camila Rocha', 'camila@teste.com');
        $this->post('pedidos-sos/criar', ['latitude' => self::RJ_LAT, 'longitude' => self::RJ_LNG]);

        $model = new PedidoSosModel();
        $this->assertSame(2, $model->countAllResults());

        $pedido1 = $model->where('mulher_id', $m1['mulherId'])->first();
        $pedido2 = $model->where('mulher_id', $m2['mulherId'])->first();
        $this->assertNotNull($pedido1);
        $this->assertNotNull($pedido2);
        $this->assertSame((float) self::SP_LAT, (float) $pedido1['latitude']);
        $this->assertSame((float) self::SP_LNG, (float) $pedido1['longitude']);
        $this->assertSame((float) self::RJ_LAT, (float) $pedido2['latitude']);
        $this->assertSame((float) self::RJ_LNG, (float) $pedido2['longitude']);
    }

    public function testMulherNaoConsegueForjarCampos(): void
    {
        $m = $this->criarMulher('Diana Alves', 'diana@teste.com');
        $outra = $this->criarMulher('Elena Nunes', 'elena@teste.com');
        $this->logarComoMulher($m['userId'], 'Diana Alves', 'diana@teste.com');

        $result = $this->post('pedidos-sos/criar', [
            'mulher_id' => $outra['mulherId'],
            'status'    => 'em_atendimento',
            'data_hora' => '2020-01-01 00:00:00',
            'latitude'  => self::SP_LAT,
            'longitude' => self::SP_LNG,
        ]);

        $result->assertRedirectTo('/mulher/dashboard');

        $pedido = (new PedidoSosModel())->first();
        $this->assertNotNull($pedido);
        $this->assertSame($m['mulherId'], (int) $pedido['mulher_id']);
        $this->assertSame('pendente', $pedido['status']);
        $this->assertSame((float) self::SP_LAT, (float) $pedido['latitude']);
        $this->assertSame((float) self::SP_LNG, (float) $pedido['longitude']);
    }

    public function testSosSemLocalizacaoNaoCriaPedido(): void
    {
        $m = $this->criarMulher('Fátima Reis', 'fatima@teste.com');
        $this->logarComoMulher($m['userId'], 'Fátima Reis', 'fatima@teste.com');

        $result = $this->post('pedidos-sos/criar');

        $result->assertRedirectTo('/mulher/dashboard');
        $result->assertSessionHas('erro');
        $this->assertSame(0, (new PedidoSosModel())->countAllResults());
    }

    public function testSosComLocalizacaoNaoNumericaNaoCriaPedido(): void
    {
        $m = $this->criarMulher('Gisele Prado', 'gisele@teste.com');
        $this->logarComoMulher($m['userId'], 'Gisele Prado', 'gisele@teste.com');

        $result = $this->post('pedidos-sos/criar', [
            'latitude'  => 'abc',
            'longitude' => 'xyz',
        ]);

        $result->assertRedirectTo('/mulher/dashboard');
        $result->assertSessionHas('erro');
        $this->assertSame(0, (new PedidoSosModel())->countAllResults());
    }

    public function testSosComLocalizacaoForaDoIntervaloNaoCriaPedido(): void
    {
        $m = $this->criarMulher('Helena Castro', 'helena@teste.com');
        $this->logarComoMulher($m['userId'], 'Helena Castro', 'helena@teste.com');

        $result = $this->post('pedidos-sos/criar', [
            'latitude'  => '200.5',
            'longitude' => '45.2',
        ]);

        $result->assertRedirectTo('/mulher/dashboard');
        $result->assertSessionHas('erro');
        $this->assertSame(0, (new PedidoSosModel())->countAllResults());
    }

    public function testPaginaSosExigeLogin(): void
    {
        $result = $this->post('pedidos-sos/criar');

        $result->assertRedirectTo('/login');
        $this->assertSame(0, (new PedidoSosModel())->countAllResults());
    }

    public function testAdminNaoCriaPedidoSos(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Admin Teste',
            'usuario_email' => 'admin@teste.com',
            'perfil'        => 'admin',
        ]);

        $result = $this->post('pedidos-sos/criar', [
            'latitude'  => self::SP_LAT,
            'longitude' => self::SP_LNG,
        ]);

        $result->assertRedirectTo('/dashboard');
        $this->assertSame(0, (new PedidoSosModel())->countAllResults());
    }

    public function testFuncionarioNaoCriaPedidoSos(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Funcionário',
            'usuario_email' => 'func@teste.com',
            'perfil'        => 'funcionario',
        ]);

        $result = $this->post('pedidos-sos/criar', [
            'latitude'  => self::SP_LAT,
            'longitude' => self::SP_LNG,
        ]);

        $result->assertRedirectTo('/dashboard');
        $this->assertSame(0, (new PedidoSosModel())->countAllResults());
    }
}