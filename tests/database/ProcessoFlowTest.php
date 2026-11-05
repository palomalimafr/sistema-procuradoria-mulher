<?php

use App\Models\AtendimentoModel;
use App\Models\MedidaProtetivaModel;
use App\Models\MulherModel;
use App\Models\ProcessoHistoricoModel;
use App\Models\ProcessoModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo PROCESSOS: criação obrigando a mulher, vínculos opcionais
 * com atendimento e medida protetiva (sempre da MESMA mulher), listagem com
 * filtros compostos, ficha com relacionamentos/histórico e edição no MESMO
 * registro. O número do processo é preservado exatamente como informado.
 *
 * @internal
 */
final class ProcessoFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    private function logarComo(string $perfil): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Equipe Teste',
            'usuario_email' => 'equipe@teste.com',
            'perfil'        => $perfil,
        ]);
    }

    private function criarMulher(array $dados = []): array
    {
        $defaults = [
            'nome_completo'   => 'Mulher Processo',
            'email'           => null,
            'cpf'             => null,
            'data_nascimento' => null,
            'zona'            => 'urbana',
            'bairro'          => 'Centro',
            'municipio'       => 'Campos dos Goytacazes',
            'estado'          => 'RJ',
            'telefone'        => null,
            'user_id'         => null,
        ];

        $dados = array_filter(array_merge($defaults, $dados), static fn ($v) => $v !== null);
        $id = (new MulherModel())->insert($dados);
        $this->assertIsInt($id);

        return ['id' => $id] + $dados;
    }

    private function criarAtendimento(int $mulherId): int
    {
        $model = new AtendimentoModel();
        $atendimento = [
            'protocolo'         => $model->proximoProtocolo(),
            'data_hora'         => date('Y-m-d H:i:s'),
            'mulher_id'         => $mulherId,
            'profissional_id'   => 1,
            'forma_atendimento' => 'presencial',
            'origem'            => 'demanda_espontanea',
            'prioridade'        => 'media',
            'situacao'          => 'novo',
            'unidade_setor'     => 'Procuradoria',
        ];

        $id = $model->insert($atendimento);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarMedida(int $mulherId): int
    {
        $model = new MedidaProtetivaModel();
        $dados = [
            'mulher_id'        => $mulherId,
            'possui_medida'    => 1,
            'numero'           => 'MP-2026-0001',
            'data_solicitacao' => date('Y-m-d'),
            'orgao'            => 'poder_judiciario',
            'situacao'         => 'concedida',
        ];

        $id = $model->insert($dados);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarProcesso(array $extra = []): array
    {
        $mulher = $this->criarMulher();
        $model = new ProcessoModel();
        $dados = array_merge([
            'mulher_id'       => $mulher['id'],
            'numero_processo' => '0001234-56.2026.8.19.0014',
            'tipo'            => 'outros',
            'orgao'           => 'poder_judiciario',
            'vara'            => '1ª Vara de Família',
            'comarca'         => 'Campos',
            'data'            => '2026-06-10',
            'situacao'        => 'em_andamento',
            'responsavel_id'  => 1,
        ], $extra);

        $id = $model->insert($dados);
        $this->assertIsInt($id);

        return $model->comDados()->where('processos.id', $id)->first();
    }

    public function testVisitanteNaoAcessaProcessos(): void
    {
        $lista = $this->get('processos');
        $lista->assertRedirectTo('/login');

        $nova = $this->get('processos/nova');
        $nova->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaProcessos(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('processos');
        $result->assertRedirectTo('/dashboard');
    }

    public function testNovoProcessoExigeMulher(): void
    {
        $this->logarComo('admin');

        $result = $this->post('processos', [
            'numero_processo' => '1234567-89.2026.8.19.0014',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testCriarProcessoRegistraTodosOsCampos(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Processo Completo']);
        $this->logarComo('admin');

        $result = $this->post('processos', [
            'mulher_id'       => $m['id'],
            'numero_processo' => '0001234-56.2026.8.19.0014',
            'tipo'            => 'outros',
            'orgao'           => 'poder_judiciario',
            'vara'            => '1ª Vara de Família',
            'comarca'         => 'Campos dos Goytacazes',
            'data'            => '2026-06-10',
            'situacao'        => 'em_andamento',
            'responsavel_id'  => 1,
            'observacoes'     => 'Acompanhar prazos mensalmente.',
        ]);

        $result->assertStatus(302);
        $this->assertStringContainsString('/processos/', $result->getRedirectUrl());

        $processo = (new ProcessoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($processo);
        $this->assertSame('0001234-56.2026.8.19.0014', $processo['numero_processo']);
        $this->assertSame('outros', $processo['tipo']);
        $this->assertSame('poder_judiciario', $processo['orgao']);
        $this->assertSame('1ª Vara de Família', $processo['vara']);
        $this->assertSame('Campos dos Goytacazes', $processo['comarca']);
        $this->assertSame('2026-06-10', $processo['data']);
        $this->assertSame('em_andamento', $processo['situacao']);
        $this->assertSame(1, (int) $processo['responsavel_id']);
        $this->assertSame(null, $processo['atendimento_id']);
        $this->assertSame(null, $processo['medida_protetiva_id']);

        $historico = (new ProcessoHistoricoModel())->where('processo_id', (int) $processo['id'])->first();
        $this->assertNotNull($historico);
        $this->assertSame('criacao', $historico['acao']);
    }

    public function testCriarProcessoComVinculosDaMesmaMulher(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Vinc Processo']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $medidaId = $this->criarMedida($m['id']);
        $this->logarComo('admin');

        $result = $this->post('processos', [
            'mulher_id'            => $m['id'],
            'atendimento_id'       => $atendimentoId,
            'medida_protetiva_id'  => $medidaId,
            'numero_processo'      => '0009999-99.2026.8.19.0014',
            'responsavel_id'       => 1,
        ]);

        $result->assertStatus(302);

        $processo = (new ProcessoModel())->orderBy('id', 'DESC')->first();
        $this->assertSame((int) $m['id'], (int) $processo['mulher_id']);
        $this->assertSame($atendimentoId, (int) $processo['atendimento_id']);
        $this->assertSame($medidaId, (int) $processo['medida_protetiva_id']);
    }

    public function testRejeitaAtendimentoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Vinc Proc A']);
        $m2 = $this->criarMulher(['nome_completo' => 'Vinc Proc B']);
        $atendimentoId = $this->criarAtendimento($m1['id']);
        $this->logarComo('admin');

        $result = $this->post('processos', [
            'mulher_id'       => $m2['id'],
            'atendimento_id'  => $atendimentoId,
            'responsavel_id'  => 1,
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new ProcessoModel())->countAllResults());
    }

    public function testRejeitaMedidaProtetivaDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Vinc Med A']);
        $m2 = $this->criarMulher(['nome_completo' => 'Vinc Med B']);
        $medidaId = $this->criarMedida($m1['id']);
        $this->logarComo('admin');

        $result = $this->post('processos', [
            'mulher_id'           => $m2['id'],
            'medida_protetiva_id' => $medidaId,
            'responsavel_id'      => 1,
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new ProcessoModel())->countAllResults());
    }

    public function testCriarProcessoSemVinculos(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Proc Simples']);
        $this->logarComo('admin');

        $result = $this->post('processos', [
            'mulher_id' => $m['id'],
        ]);

        $result->assertStatus(302);

        $processo = (new ProcessoModel())->orderBy('id', 'DESC')->first();
        $this->assertNull($processo['atendimento_id']);
        $this->assertNull($processo['medida_protetiva_id']);
        $this->assertNull($processo['numero_processo']);
        $this->assertSame('outros', $processo['tipo']);
        $this->assertSame('em_andamento', $processo['situacao']);
    }

    public function testListagemExibeProcessos(): void
    {
        $this->criarProcesso(['numero_processo' => '0000444-44.2026.8.19.0014']);
        $this->logarComo('admin');

        $result = $this->get('processos');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('0000444-44.2026.8.19.0014', $body);
        $this->assertStringContainsString('Mulher Processo', $body);
        $this->assertStringContainsString('Em andamento', $body);
        $this->assertStringContainsString('1ª Vara de Família', html_entity_decode($body, ENT_QUOTES, 'UTF-8'));
    }

    public function testFiltroPorSituacao(): void
    {
        $this->criarProcesso(['numero_processo' => 'Proc Andamento', 'situacao' => 'em_andamento']);
        $this->criarProcesso(['numero_processo' => 'Proc Encerrado', 'situacao' => 'encerrado']);
        $this->logarComo('admin');

        $result = $this->get('processos?situacao=encerrado');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Proc Encerrado', $body);
        $this->assertStringNotContainsString('Proc Andamento', $body);
    }

    public function testFiltroPorResponsavel(): void
    {
        $segundo = (new UserModel())->insert([
            'nome'   => 'Segunda Referencia Proc',
            'email'  => 'segunda-proc@teste.com',
            'senha'  => 'senha123',
            'perfil' => 'funcionario',
            'ativo'  => 1,
        ]);
        $this->assertIsInt($segundo);

        $this->criarProcesso(['numero_processo' => 'Proc Resp 1', 'responsavel_id' => 1]);
        $this->criarProcesso(['numero_processo' => 'Proc Resp 2', 'responsavel_id' => $segundo]);
        $this->logarComo('admin');

        $result = $this->get('processos?responsavel=1');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Proc Resp 1', $body);
        $this->assertStringNotContainsString('Proc Resp 2', $body);
    }

    public function testFiltroPorPeriodoDeData(): void
    {
        $this->criarProcesso(['numero_processo' => 'Proc Dentro', 'data' => '2026-06-15']);
        $this->criarProcesso(['numero_processo' => 'Proc Fora', 'data' => '2027-01-01']);
        $this->logarComo('admin');

        $result = $this->get('processos?data_de=2026-06-01&data_ate=2026-06-30');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Proc Dentro', $body);
        $this->assertStringNotContainsString('Proc Fora', $body);
    }

    public function testDetalhesExibeDadosERelacionamentos(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Ficha Processo']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $medidaId = $this->criarMedida($m['id']);
        $p = $this->criarProcesso([
            'numero_processo'     => '0007777-77.2026.8.19.0014',
            'mulher_id'           => $m['id'],
            'atendimento_id'      => $atendimentoId,
            'medida_protetiva_id' => $medidaId,
        ]);
        $this->logarComo('admin');

        $result = $this->get('processos/' . (int) $p['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Processo #' . $p['id'], $body);
        $this->assertStringContainsString('Dados do processo', $body);
        $this->assertStringContainsString('0007777-77.2026.8.19.0014', $body);
        $this->assertStringContainsString('Ficha Processo', $body);
        $this->assertStringContainsString($p['atendimento_protocolo'], $body);
        $this->assertStringContainsString('Relacionamentos', $body);
        $this->assertStringContainsString('Histórico do processo', html_entity_decode($body, ENT_QUOTES, 'UTF-8'));
        $this->assertStringContainsString('Editar processo', $body);
    }

    public function testEditarMantemMesmoRegistro(): void
    {
        $p = $this->criarProcesso();
        $this->logarComo('admin');

        $result = $this->post('processos/atualizar/' . (int) $p['id'], [
            'mulher_id'       => (int) $p['mulher_id'],
            'numero_processo' => '0001111-11.2026.8.19.0014',
            'tipo'            => 'outros',
            'orgao'           => 'defensoria_publica',
            'vara'            => '2ª Vara de Família',
            'comarca'         => 'Campos',
            'data'            => '2026-07-01',
            'situacao'        => 'em_andamento',
            'responsavel_id'  => 1,
        ]);

        $result->assertRedirectTo('/processos/' . (int) $p['id']);

        $model = new ProcessoModel();
        $this->assertSame(1, $model->where('id', (int) $p['id'])->countAllResults());
        $atual = $model->find((int) $p['id']);
        $this->assertSame('0001111-11.2026.6.8.19.0014' !== '' ? '0001111-11.2026.8.19.0014' : '', $atual['numero_processo']);
        $this->assertSame('defensoria_publica', $atual['orgao']);
        $this->assertSame('2ª Vara de Família', $atual['vara']);
        $this->assertSame('2026-07-01', $atual['data']);
    }

    public function testEditarRegistraHistoricoDeSituacao(): void
    {
        $p = $this->criarProcesso(['situacao' => 'em_andamento']);
        $this->logarComo('admin');

        $result = $this->post('processos/atualizar/' . (int) $p['id'], [
            'mulher_id'      => (int) $p['mulher_id'],
            'numero_processo' => $p['numero_processo'] ?? '',
            'tipo'           => 'outros',
            'orgao'          => $p['orgao'] ?? '',
            'vara'           => $p['vara'] ?? '',
            'comarca'        => $p['comarca'] ?? '',
            'data'           => $p['data'] ?? '',
            'situacao'       => 'encerrado',
            'responsavel_id' => 1,
        ]);

        $result->assertRedirectTo('/processos/' . (int) $p['id']);
        $this->assertSame('encerrado', (new ProcessoModel())->find((int) $p['id'])['situacao']);

        $situacao = (new ProcessoHistoricoModel())
            ->where('processo_id', (int) $p['id'])
            ->where('acao', 'situacao')
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($situacao);
        $this->assertSame('em_andamento', $situacao['status_anterior']);
        $this->assertSame('encerrado', $situacao['status_novo']);
    }

    public function testPaginaNovaCarregaComMulherPreselecionada(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Processo']);
        $this->logarComo('admin');

        $result = $this->get('processos/nova?mulher_id=' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Novo Processo', $body);
        $this->assertStringContainsString('Preselecao Processo', $body);
        $this->assertStringContainsString('value="' . $m['id'] . '"', $body);
    }

    public function testPaginaNovaCarregaComAtendimentoVinculado(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Proc Atend']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $this->logarComo('admin');

        $result = $this->get('processos/nova?atendimento_id=' . $atendimentoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Preselecao Proc Atend', $body);
        $this->assertStringContainsString('value="' . $atendimentoId . '"', $body);
    }
}