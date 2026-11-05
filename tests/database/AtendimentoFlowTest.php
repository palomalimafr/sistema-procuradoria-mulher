<?php

use App\Models\AtendimentoModel;
use App\Models\MulherModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo ATENDIMENTOS (novo → fila → em andamento → encerrado).
 *
 * @internal
 */
final class AtendimentoFlowTest extends CIUnitTestCase
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
            'user_id'           => $userId,
            'nome_completo'     => $nome,
            'email'             => $email,
            'cpf'               => '52998224725',
            'data_nascimento'   => '1990-05-10',
            'bairro'            => 'Centro',
            'municipio'         => 'Campos dos Goytacazes',
        ]);

        $this->assertIsInt($mulherId);

        return ['userId' => $userId, 'mulherId' => $mulherId, 'nome' => $nome];
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

    private function dadosAtendimento(array $m, array $extra = []): array
    {
        return array_merge([
            'mulher_id'          => (string) $m['mulherId'],
            'data_hora'          => date('Y-m-d\TH:i'),
            'forma_atendimento'  => 'presencial',
            'origem'             => 'demanda_espontanea',
            'prioridade'         => 'alta',
            'situacao'           => 'novo',
            'unidade_setor'      => 'Procuradoria',
            'motivo_atendimento' => 'Ameaças sofridas',
            'descricao_caso'     => 'Relato de ameaças recentes.',
        ], $extra);
    }

    public function testTelaNovoAtendimentoCarregaParaEquipe(): void
    {
        $this->logarComoAdmin();
        $result = $this->get('atendimentos/novo');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Novo Atendimento', $body);
        $this->assertStringContainsString('Mulher atendida', $body);
        $this->assertStringContainsString('busca_mulher', $body);
        $this->assertStringContainsString('AT-', $body);
    }

    public function testCriarAtendimentoAdicionaNaFila(): void
    {
        $m = $this->criarMulher('Maria Silva', 'maria.atend@teste.com');
        $this->logarComoAdmin();

        $result = $this->post('atendimentos', $this->dadosAtendimento($m));
        $result->assertRedirectTo('/atendimentos/fila');

        $atendimento = (new AtendimentoModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($atendimento);
        $this->assertSame('novo', $atendimento['situacao']);
        $this->assertSame('alta', $atendimento['prioridade']);
        $this->assertSame('AT-' . date('y') . '-000001', $atendimento['protocolo']);

        $lista = $this->get('atendimentos/fila');
        $lista->assertStatus(200);
        $body = $lista->getBody();
        $this->assertStringContainsString($atendimento['protocolo'], $body);
        $this->assertStringContainsString('Maria Silva', $body);
        $this->assertStringContainsString('Alta', $body);
    }

    public function testCriarAtendimentoExigeMulher(): void
    {
        $this->logarComoAdmin();

        $result = $this->post('atendimentos', $this->dadosAtendimento([
            'mulherId' => 0,
        ], ['mulher_id' => '']));

        $result->assertRedirect();
        $result->assertSessionHas('erros');
    }

    public function testBuscaDeMulheresRetornaJson(): void
    {
        $this->criarMulher('Joana Melo', 'joana.busca@teste.com');
        $this->logarComoAdmin();

        $result = $this->get('atendimentos/buscar-mulheres?term=Joana');

        $result->assertStatus(200);
        $json = json_decode((string) $result->getJSON(), true);
        $this->assertNotEmpty($json['mulheres']);
        $this->assertSame('Joana Melo', $json['mulheres'][0]['nome']);
        $this->assertSame('52998224725', $json['mulheres'][0]['cpf']);
    }

    public function testIniciarMudaParaEmAndamento(): void
    {
        $m = $this->criarMulher('Bia Andrade', 'bia.ini@teste.com');
        $this->logarComoAdmin();
        $this->post('atendimentos', $this->dadosAtendimento($m));

        $atendimento = (new AtendimentoModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($atendimento);

        $result = $this->post('atendimentos/' . $atendimento['id'] . '/iniciar');
        $result->assertRedirectTo('/atendimentos/em-andamento');

        $this->assertSame('em_andamento', (new AtendimentoModel())->find($atendimento['id'])['situacao']);

        $lista = $this->get('atendimentos/em-andamento');
        $lista->assertStatus(200);
        $this->assertStringContainsString($atendimento['protocolo'], $lista->getBody());

        $fila = $this->get('atendimentos/fila');
        $this->assertStringNotContainsString($atendimento['protocolo'], $fila->getBody());
    }

    public function testEncerrarExigeMotivo(): void
    {
        $m = $this->criarMulher('Carla Reis', 'carla.enc@teste.com');
        $this->logarComoAdmin();
        $this->post('atendimentos', $this->dadosAtendimento($m));

        $atendimento = (new AtendimentoModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($atendimento);

        $semMotivo = $this->post('atendimentos/' . $atendimento['id'] . '/encerrar', []);
        $semMotivo->assertRedirect();
        $semMotivo->assertSessionHas('erros');

        $this->assertSame('novo', (new AtendimentoModel())->find($atendimento['id'])['situacao']);

        $encerra = $this->post('atendimentos/' . $atendimento['id'] . '/encerrar', [
            'motivo_encerramento' => 'caso_resolvido',
        ]);
        $encerra->assertRedirectTo('/atendimentos/encerrados');

        $registro = (new AtendimentoModel())->find($atendimento['id']);
        $this->assertSame('encerrado', $registro['situacao']);
        $this->assertSame('caso_resolvido', $registro['motivo_encerramento']);
        $this->assertNotNull($registro['data_encerramento']);

        $lista = $this->get('atendimentos/encerrados');
        $lista->assertStatus(200);
        $this->assertStringContainsString($atendimento['protocolo'], $lista->getBody());
        $this->assertStringContainsString('Caso resolvido', $lista->getBody());
    }

    public function testEncerrarOutrosExigeDetalhe(): void
    {
        $m = $this->criarMulher('Duda Prado', 'duda.outro@teste.com');
        $this->logarComoAdmin();
        $this->post('atendimentos', $this->dadosAtendimento($m));

        $atendimento = (new AtendimentoModel())
            ->where('mulher_id', $m['mulherId'])
            ->orderBy('id', 'DESC')
            ->first();

        $semDetalhe = $this->post('atendimentos/' . $atendimento['id'] . '/encerrar', [
            'motivo_encerramento' => 'outros',
        ]);
        $semDetalhe->assertRedirect();
        $semDetalhe->assertSessionHas('erros');

        $this->post('atendimentos/' . $atendimento['id'] . '/encerrar', [
            'motivo_encerramento'       => 'outros',
            'motivo_encerramento_outra' => 'Transferência interna',
        ]);

        $registro = (new AtendimentoModel())->find($atendimento['id']);
        $this->assertSame('encerrado', $registro['situacao']);
        $this->assertSame('Transferência interna', $registro['motivo_encerramento_outra']);
    }

    public function testMulherNaoAcessaAtendimentos(): void
    {
        $m = $this->criarMulher('Eva Lima', 'eva.bloqueio@teste.com');
        $this->logarComoMulher($m['userId'], 'Eva Lima', 'eva.bloqueio@teste.com');

        $result = $this->get('atendimentos/fila');
        $result->assertRedirectTo('/dashboard');
        $result->assertSessionHas('erro');

        $resultNovo = $this->get('atendimentos/novo');
        $resultNovo->assertRedirectTo('/dashboard');

        $resultJson = $this->get('atendimentos/buscar-mulheres?term=Eva');
        $resultJson->assertRedirectTo('/dashboard');
    }

    public function testProtocoloEUnicoParaAtendimentosDiferentes(): void
    {
        $m = $this->criarMulher('Fabi Neves', 'fabi.n.prot@teste.com');
        $m2 = $this->criarMulher('Gabi Tavares', 'gabi.n.prot@teste.com');
        $this->logarComoAdmin();

        $this->post('atendimentos', $this->dadosAtendimento($m));
        $this->post('atendimentos', $this->dadosAtendimento($m2));

        $model = new AtendimentoModel();
        $todos = $model->orderBy('id', 'ASC')->findAll();

        $protocolos = array_column($todos, 'protocolo');
        $this->assertCount(2, $protocolos);
        $this->assertSame(2, count(array_unique($protocolos)));
    }
}