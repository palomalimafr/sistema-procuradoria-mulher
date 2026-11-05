<?php

use App\Models\AcolhimentoHistoricoModel;
use App\Models\AcolhimentoModel;
use App\Models\AtendimentoModel;
use App\Models\AvaliacaoRiscoModel;
use App\Models\MulherModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo ACOLHIMENTO: novo acolhimento, consulta,
 * conclusão, histórico e avaliação de risco.
 *
 * @internal
 */
final class AcolhimentoFlowTest extends CIUnitTestCase
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
            'nome_completo'   => 'Mulher Acolhimento',
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

    private function criarAtendimento(int $mulherId, array $extra = []): int
    {
        $model = new AtendimentoModel();
        $atendimento = array_merge([
            'protocolo'         => $model->proximoProtocolo(),
            'data_hora'         => date('Y-m-d H:i:s'),
            'mulher_id'         => $mulherId,
            'profissional_id'   => 1,
            'forma_atendimento' => 'presencial',
            'origem'            => 'demanda_espontanea',
            'prioridade'        => 'media',
            'situacao'          => 'novo',
            'unidade_setor'     => 'Procuradoria',
        ], $extra);

        $id = $model->insert($atendimento);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarAcolhimento(int $atendimentoId, array $extra = []): int
    {
        $model = new AcolhimentoModel();
        $dados = array_merge([
            'atendimento_id'   => $atendimentoId,
            'data_hora'        => date('Y-m-d H:i:s'),
            'profissional_id'  => 1,
            'status'           => 'em_andamento',
            'motivo_procura'   => 'Procura ajuda',
        ], $extra);

        $id = $model->insert($dados);
        $this->assertIsInt($id);

        return $id;
    }

    public function testVisitanteNaoAcessaAcolhimentosNemAvaliacoes(): void
    {
        $acolhimentos = $this->get('acolhimentos');
        $acolhimentos->assertRedirectTo('/login');

        $avaliacoes = $this->get('avaliacoes');
        $avaliacoes->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaAcolhimentos(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('acolhimentos');
        $result->assertRedirectTo('/dashboard');
    }

    public function testNovoAcolhimentoExigeAtendimentoSelecionado(): void
    {
        $this->logarComo('admin');

        $result = $this->post('acolhimentos', [
            'data_hora'       => '2026-01-10 10:00',
            'motivo_procura'  => 'Sem atendimento',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testPaginaNovoCarregaComAtendimentoPreselecionado(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Acolhimento']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $this->logarComo('admin');

        $result = $this->get('acolhimentos/novo?atendimento_id=' . $atendimentoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Novo Acolhimento', $body);
        $this->assertStringContainsString('Preselecao Acolhimento', $body);
        $this->assertStringContainsString('value="' . $atendimentoId . '"', $body);
    }

    public function testCriarAcolhimentoRegistraComUsuarioAutenticadoEHistorico(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Fluxo Completo']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $this->logarComo('admin');

        $result = $this->post('acolhimentos', [
            'atendimento_id'         => $atendimentoId,
            'data_hora'              => '2026-01-10 10:00',
            'motivo_procura'         => 'Pedido de orientação',
            'situacao_apresentada'   => 'Situação relatada pela mulher',
            'necessidades_imediatas' => 'Apoio imediato',
            'necessidade_protecao'   => '1',
            'necessidade_juridica'   => '1',
            'outras_necessidades'    => 'Apoio escolar',
            'risco_identificado'     => 'medio',
            'observacoes'            => 'Atendimento acolhedor',
        ]);

        $result->assertStatus(302);
        $this->assertStringContainsString('/acolhimentos/', $result->getRedirectUrl());

        $acolhimento = (new AcolhimentoModel())
            ->where('atendimento_id', $atendimentoId)
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($acolhimento);
        $this->assertSame('em_andamento', $acolhimento['status']);
        $this->assertSame(1, (int) $acolhimento['profissional_id']);
        $this->assertSame('Pedido de orientação', $acolhimento['motivo_procura']);
        $this->assertSame(1, (int) $acolhimento['necessidade_protecao']);
        $this->assertSame('Apoio escolar', $acolhimento['outras_necessidades']);
        $this->assertSame('medio', $acolhimento['risco_identificado']);

        $historico = (new AcolhimentoHistoricoModel())
            ->where('acolhimento_id', $acolhimento['id'])
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($historico);
        $this->assertSame('em_andamento', $historico['status_novo']);
        $this->assertSame(1, (int) $historico['usuario_id']);
    }

    public function testConsultaListaAcolhimentos(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Consulta Acolhimento']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $this->criarAcolhimento($atendimentoId);
        $this->logarComo('admin');

        $result = $this->get('acolhimentos');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Consulta Acolhimento', $body);
        $this->assertStringContainsString('Novo acolhimento', $body);
        $this->assertStringContainsString('Em andamento', $body);
    }

    public function testDetalhesExibeCamposNecessidadesERisco(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Ficha Acolhimento']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $id = $this->criarAcolhimento($atendimentoId, [
            'necessidade_psicologica' => 1,
            'necessidade_seguranca'   => 1,
            'risco_identificado'      => 'alto',
        ]);
        $this->logarComo('admin');

        $result = $this->get('acolhimentos/' . $id);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Ficha Acolhimento', $body);
        $this->assertStringContainsString('Motivo da procura', $body);
        $this->assertStringContainsString('Procura ajuda', $body);
        $this->assertStringContainsString('Psicol', $body);
        $this->assertStringContainsString('Seguran', $body);
        $this->assertStringContainsString('Alto', $body);
        $this->assertStringContainsString('Avaliar risco', $body);
        $this->assertStringContainsString('Concluir acolhimento', $body);
    }

    public function testConcluirRegistraStatusDataEHistorico(): void
    {
        $m = $this->criarMulher();
        $atendimentoId = $this->criarAtendimento($m['id']);
        $id = $this->criarAcolhimento($atendimentoId);
        $this->logarComo('admin');

        $result = $this->post('acolhimentos/' . $id . '/concluir');

        $result->assertRedirectTo('/acolhimentos/' . $id);

        $atual = (new AcolhimentoModel())->find($id);
        $this->assertSame('concluido', $atual['status']);
        $this->assertNotNull($atual['data_conclusao']);

        $historico = (new AcolhimentoHistoricoModel())
            ->where('acolhimento_id', $id)
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertSame('em_andamento', $historico['status_anterior']);
        $this->assertSame('concluido', $historico['status_novo']);
    }

    public function testConcluirSegundaVezRetornaErro(): void
    {
        $m = $this->criarMulher();
        $atendimentoId = $this->criarAtendimento($m['id']);
        $id = $this->criarAcolhimento($atendimentoId, ['status' => 'concluido', 'data_conclusao' => date('Y-m-d H:i:s')]);
        $this->logarComo('admin');

        $result = $this->post('acolhimentos/' . $id . '/concluir');

        $result->assertSessionHas('erro');
    }

    public function testFiltroStatusConcluido(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Concluido Filtro']);
        $atendIdA = $this->criarAtendimento($m['id']);
        $this->criarAcolhimento($atendIdA, ['status' => 'concluido']);

        $m2 = $this->criarMulher(['nome_completo' => 'EmAndamento Filtro']);
        $atendIdB = $this->criarAtendimento($m2['id']);
        $this->criarAcolhimento($atendIdB);
        $this->logarComo('admin');

        $result = $this->get('acolhimentos?status=concluido');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Concluido Filtro', $body);
        $this->assertStringNotContainsString('EmAndamento Filtro', $body);
    }

    public function testBuscarAtendimentosAjax(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Busca Acolhimento AJAX']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $protocolo = (new AtendimentoModel())->find($atendimentoId)['protocolo'];
        $this->logarComo('admin');

        $result = $this->get('acolhimentos/buscar-atendimentos?term=' . $protocolo);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString($protocolo, $body);
        $this->assertStringContainsString('Busca Acolhimento AJAX', $body);
    }

    public function testAvaliacaoSalvaComSugestaoEClassificacaoProfissional(): void
    {
        $m = $this->criarMulher();
        $atendimentoId = $this->criarAtendimento($m['id']);
        $id = $this->criarAcolhimento($atendimentoId);
        $this->logarComo('admin');

        $result = $this->post('avaliacoes', [
            'acolhimento_id'   => $id,
            'ameaca_morte'     => '1',
            'agressor_arma'    => '1',
            'observacoes_profissionais' => 'Mulher em situação grave',
            // O profissional ajusta a classificação para "alto".
            'classificacao'    => 'alto',
        ]);

        $result->assertRedirectTo('/acolhimentos/' . $id);

        $avaliacao = (new AvaliacaoRiscoModel())
            ->where('acolhimento_id', $id)
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($avaliacao);
        $this->assertSame('alto', $avaliacao['classificacao']);
        $this->assertSame('extremo', $avaliacao['classificacao_sugerida']);
        $this->assertSame(1, (int) $avaliacao['profissional_id']);
        $this->assertSame('1', (string) $avaliacao['ameaca_morte']);

        $resultado = $this->get('avaliacoes/' . $avaliacao['id']);
        $resultado->assertStatus(200);
        $body = $resultado->getBody();
        $this->assertStringContainsString('Alto', $body);
        $this->assertStringContainsString('Extremo', $body);
        $this->assertStringContainsString('Existe amea', $body);
    }

    public function testAvaliacaoExigeClassificacaoDoProfissional(): void
    {
        $m = $this->criarMulher();
        $atendimentoId = $this->criarAtendimento($m['id']);
        $id = $this->criarAcolhimento($atendimentoId);
        $this->logarComo('admin');

        $result = $this->post('avaliacoes', [
            'acolhimento_id' => $id,
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testAvaliacaoSemRespostasSugereRiscoBaixo(): void
    {
        $m = $this->criarMulher();
        $atendimentoId = $this->criarAtendimento($m['id']);
        $id = $this->criarAcolhimento($atendimentoId);
        $this->logarComo('admin');

        $this->post('avaliacoes', [
            'acolhimento_id' => $id,
            'classificacao'  => 'baixo',
        ]);

        $avaliacao = (new AvaliacaoRiscoModel())
            ->where('acolhimento_id', $id)
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertSame('baixo', $avaliacao['classificacao_sugerida']);
    }

    public function testPaginaNovaAvaliacaoExigeAcolhimento(): void
    {
        $this->logarComo('admin');

        $result = $this->get('avaliacoes/novo/999999');

        $result->assertRedirectTo('/acolhimentos');
        $result->assertSessionHas('erro');
    }
}