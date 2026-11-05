<?php

use App\Models\AtendimentoModel;
use App\Models\EncaminhamentoModel;
use App\Models\InstituicaoModel;
use App\Models\MulherModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo ENCAMINHAMENTOS: novo encaminhamento, pendentes,
 * concluídos, acompanhamento de status e relacionamentos com mulher/atendimento.
 *
 * @internal
 */
final class EncaminhamentoFlowTest extends CIUnitTestCase
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
            'nome_completo'   => 'Mulher Encaminhamento',
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

    private function criarEncaminhamento(int $mulherId, array $extra = []): array
    {
        $model = new EncaminhamentoModel();
        $dados = array_merge([
            'mulher_id'        => $mulherId,
            'data_hora'        => date('Y-m-d H:i:s'),
            'profissional_id'  => 1,
            'status'           => 'pendente',
            'orgao'            => 'creas',
            'servico'          => 'psicologico',
            'motivo'           => 'Acompanhamento psicossocial',
            'prioridade'       => 'media',
        ], $extra);

        $id = $model->insert($dados);
        $this->assertIsInt($id);

        return $model->find($id);
    }

    public function testVisitanteNaoAcessaEncaminhamentos(): void
    {
        $novo = $this->get('encaminhamentos/novo');
        $novo->assertRedirectTo('/login');

        $pendentes = $this->get('encaminhamentos/pendentes');
        $pendentes->assertRedirectTo('/login');

        $concluidos = $this->get('encaminhamentos/concluidos');
        $concluidos->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaEncaminhamentos(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('encaminhamentos/pendentes');
        $result->assertRedirectTo('/dashboard');
    }

    public function testNovoEncaminhamentoExigeMulher(): void
    {
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos', [
            'orgao' => 'creas',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testNovoEncaminhamentoExigeOrgaoValido(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Sem Orgao']);
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos', [
            'mulher_id' => $m['id'],
            'orgao'     => '',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testCriarEncaminhamentoRegistraComUsuarioAutenticado(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Fluxo Encaminhamento']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $instituicaoId = (new InstituicaoModel())->insert([
            'nome'  => 'DEAM Campos',
            'tipo'  => 'seguranca_publica',
            'ativo' => 1,
        ]);
        $this->assertIsInt($instituicaoId);
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos', [
            'mulher_id'     => $m['id'],
            'atendimento_id' => $atendimentoId,
            'data_hora'     => '2026-01-15 09:30',
            'instituicao_id' => $instituicaoId,
            'servico'       => 'protecao',
            'motivo'        => 'Acompanhamento de medida',
            'prioridade'    => 'alta',
            'prazo'         => '2026-02-01',
            'observacoes'   => 'Retorno esperado em 15 dias',
        ]);

        $result->assertStatus(302);
        $this->assertStringContainsString('/encaminhamentos/', $result->getRedirectUrl());

        $encaminhamento = (new EncaminhamentoModel())
            ->where('mulher_id', $m['id'])
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($encaminhamento);
        $this->assertSame('pendente', $encaminhamento['status']);
        $this->assertSame(1, (int) $encaminhamento['profissional_id']);
        $this->assertSame((int) $atendimentoId, (int) $encaminhamento['atendimento_id']);
        $this->assertSame($instituicaoId, (int) $encaminhamento['instituicao_id']);
        $this->assertSame('DEAM Campos', $encaminhamento['orgao']);
        $this->assertSame('protecao', $encaminhamento['servico']);
        $this->assertSame('alta', $encaminhamento['prioridade']);
        $this->assertSame('2026-02-01', $encaminhamento['prazo']);
    }

    public function testPaginaNovoCarregaComMulherPreselecionada(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Encaminhamento']);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/novo?mulher_id=' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Novo Encaminhamento', $body);
        $this->assertStringContainsString('Preselecao Encaminhamento', $body);
        $this->assertStringContainsString('value="' . $m['id'] . '"', $body);
    }

    public function testPaginaNovoCarregaComAtendimentoPreselecionado(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Atendimento']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/novo?atendimento_id=' . $atendimentoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Preselecao Atendimento', $body);
        $this->assertStringContainsString('value="' . $atendimentoId . '"', $body);
    }

    public function testRejeitaAtendimentoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Mulher A']);
        $m2 = $this->criarMulher(['nome_completo' => 'Mulher B']);
        $atendimentoId = $this->criarAtendimento($m1['id']);
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos', [
            'mulher_id'     => $m2['id'],
            'atendimento_id' => $atendimentoId,
            'orgao'         => 'creas',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testPendentesListaSomenteEmAcompanhamento(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Pendente Visivel']);
        $this->criarEncaminhamento($m1['id']);

        $m2 = $this->criarMulher(['nome_completo' => 'Concluido Oculto']);
        $this->criarEncaminhamento($m2['id'], [
            'status' => 'concluido',
            'data_conclusao' => date('Y-m-d H:i:s'),
        ]);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/pendentes');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Pendente Visivel', $body);
        $this->assertStringContainsString('Pendente', $body);
        $this->assertStringNotContainsString('Concluido Oculto', $body);
    }

    public function testConcluidosListaSomenteConcluidos(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Pendente Oculto']);
        $this->criarEncaminhamento($m1['id']);

        $m2 = $this->criarMulher(['nome_completo' => 'Concluido Visivel']);
        $this->criarEncaminhamento($m2['id'], [
            'status' => 'concluido',
            'data_conclusao' => date('Y-m-d H:i:s'),
        ]);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/concluidos');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Concluido Visivel', $body);
        $this->assertStringNotContainsString('Pendente Oculto', $body);
    }

    public function testDetalhesExibeCamposEStatus(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Ficha Encaminhamento']);
        $e = $this->criarEncaminhamento($m['id'], [
            'orgao'      => 'creas',
            'servico'    => 'psicologico',
            'prioridade' => 'alta',
            'prazo'      => '2026-02-01',
            'motivo'     => 'Necessita apoio psicossocial',
        ]);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/' . $e['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Encaminhamento #' . $e['id'], $body);
        $this->assertStringContainsString('Ficha Encaminhamento', $body);
        $this->assertStringContainsString('CREAS', $body);
        $this->assertStringContainsString('psicol', $body);
        $this->assertStringContainsString('Alta', $body);
        $this->assertStringContainsString('01/02/2026', $body);
        $this->assertStringContainsString('Pendente', $body);
        $this->assertStringContainsString('Acompanhamento do encaminhamento', $body);
        $this->assertStringContainsString('Encaminhado', $body);
        $this->assertStringContainsString('Cancelar encaminhamento', $body);
    }

    public function testFluxoDeStatusAteConcluido(): void
    {
        $m = $this->criarMulher();
        $e = $this->criarEncaminhamento($m['id']);
        $model = new EncaminhamentoModel();
        $this->logarComo('admin');

        $fluxo = ['encaminhado', 'em_atendimento', 'aguardando_retorno', 'concluido'];
        foreach ($fluxo as $status) {
            $result = $this->post('encaminhamentos/' . $e['id'] . '/status', ['status' => $status]);
            $result->assertRedirectTo('/encaminhamentos/' . $e['id']);
        }

        $atual = $model->find($e['id']);
        $this->assertSame('concluido', $atual['status']);
        $this->assertNotNull($atual['data_conclusao']);
    }

    public function testCancelarEncaminhamento(): void
    {
        $m = $this->criarMulher();
        $e = $this->criarEncaminhamento($m['id']);
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos/' . $e['id'] . '/status', ['status' => 'cancelado']);

        $result->assertRedirectTo('/encaminhamentos/' . $e['id']);
        $this->assertSame('cancelado', (new EncaminhamentoModel())->find($e['id'])['status']);
    }

    public function testTransicaoInvalidaNaoPermitida(): void
    {
        $m = $this->criarMulher();
        $e = $this->criarEncaminhamento($m['id']);
        $this->logarComo('admin');

        // Pendente não pode ir direto para concluido/cancelado fora da ordem.
        $result = $this->post('encaminhamentos/' . $e['id'] . '/status', ['status' => 'concluido']);

        $result->assertSessionHas('erro');
        $this->assertSame('pendente', (new EncaminhamentoModel())->find($e['id'])['status']);
    }

    public function testStatusConcluidoNaoAceitaNovasTransicoes(): void
    {
        $m = $this->criarMulher();
        $e = $this->criarEncaminhamento($m['id'], ['status' => 'concluido', 'data_conclusao' => date('Y-m-d H:i:s')]);
        $this->logarComo('admin');

        $result = $this->post('encaminhamentos/' . $e['id'] . '/status', ['status' => 'encaminhado']);

        $result->assertSessionHas('erro');
        $this->assertSame('concluido', (new EncaminhamentoModel())->find($e['id'])['status']);
    }

    public function testFiltroPorPrioridade(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Prioridade Alta']);
        $this->criarEncaminhamento($m1['id'], ['prioridade' => 'alta']);

        $m2 = $this->criarMulher(['nome_completo' => 'Prioridade Baixa']);
        $this->criarEncaminhamento($m2['id'], ['prioridade' => 'baixa']);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/pendentes?prioridade=alta');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Prioridade Alta', $body);
        $this->assertStringNotContainsString('Prioridade Baixa', $body);
    }

    public function testFiltroPorOrgao(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Orgao CREAS']);
        $this->criarEncaminhamento($m1['id'], ['orgao' => 'creas']);

        $m2 = $this->criarMulher(['nome_completo' => 'Orgao CRAS']);
        $this->criarEncaminhamento($m2['id'], ['orgao' => 'cras']);
        $this->logarComo('admin');

        $result = $this->get('encaminhamentos/pendentes?orgao=creas');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Orgao CREAS', $body);
        $this->assertStringNotContainsString('Orgao CRAS', $body);
    }
}