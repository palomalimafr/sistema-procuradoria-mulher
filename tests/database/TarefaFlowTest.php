<?php

use App\Models\AtendimentoModel;
use App\Models\EncaminhamentoModel;
use App\Models\MulherModel;
use App\Models\TarefaModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo TAREFAS (subopção de Agenda): criação, vínculos,
 * visões como filtros sobre a mesma tabela, fluxo de status com data de
 * conclusão no MESMO registro (nunca apaga) e atraso por prazo.
 *
 * @internal
 */
final class TarefaFlowTest extends CIUnitTestCase
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
            'nome_completo'   => 'Mulher Tarefa',
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

    private function criarEncaminhamento(int $mulherId): int
    {
        $model = new EncaminhamentoModel();
        $dados = [
            'mulher_id'       => $mulherId,
            'data_hora'       => date('Y-m-d H:i:s'),
            'profissional_id' => 1,
            'status'          => 'pendente',
            'orgao'           => 'creas',
            'servico'         => 'psicologico',
            'motivo'          => 'Acompanhamento psicossocial',
            'prioridade'      => 'media',
        ];

        $id = $model->insert($dados);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarTarefa(array $extra = []): array
    {
        $model = new TarefaModel();
        $dados = array_merge([
            'titulo'         => 'Entrar em contato com a assistida',
            'responsavel_id' => 1,
            'prioridade'     => 'media',
            'status'         => 'pendente',
        ], $extra);

        $id = $model->insert($dados);
        $this->assertIsInt($id);

        return $model->find($id);
    }

    public function testVisitanteNaoAcessaTarefas(): void
    {
        $lista = $this->get('tarefas');
        $lista->assertRedirectTo('/login');

        $nova = $this->get('tarefas/nova');
        $nova->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaTarefas(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('tarefas');
        $result->assertRedirectTo('/dashboard');
    }

    public function testNovaTarefaExigeTitulo(): void
    {
        $this->logarComo('admin');

        $result = $this->post('tarefas', [
            'titulo' => '',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testCriarTarefaRegistraPendente(): void
    {
        $this->logarComo('admin');

        $result = $this->post('tarefas', [
            'titulo'         => 'Verificar encaminhamento',
            'descricao'      => 'Ligar para o orgao',
            'responsavel_id' => 1,
            'prazo'          => '2026-06-20',
            'prioridade'     => 'alta',
        ]);

        $result->assertStatus(302);
        $this->assertStringContainsString('/tarefas/', $result->getRedirectUrl());

        $tarefa = (new TarefaModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($tarefa);
        $this->assertSame('pendente', $tarefa['status']);
        $this->assertSame(1, (int) $tarefa['responsavel_id']);
        $this->assertSame('alta', $tarefa['prioridade']);
        $this->assertSame('2026-06-20', $tarefa['prazo']);
        $this->assertNull($tarefa['data_conclusao']);
    }

    public function testCriarTarefaVinculada(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Tarefa Vinculada']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $encaminhamentoId = $this->criarEncaminhamento($m['id']);
        $this->logarComo('admin');

        $result = $this->post('tarefas', [
            'titulo'           => 'Acompanhar caso',
            'mulher_id'        => $m['id'],
            'atendimento_id'   => $atendimentoId,
            'encaminhamento_id' => $encaminhamentoId,
            'responsavel_id'   => 1,
            'prioridade'       => 'media',
        ]);

        $result->assertStatus(302);

        $tarefa = (new TarefaModel())->orderBy('id', 'DESC')->first();
        $this->assertSame((int) $m['id'], (int) $tarefa['mulher_id']);
        $this->assertSame($atendimentoId, (int) $tarefa['atendimento_id']);
        $this->assertSame($encaminhamentoId, (int) $tarefa['encaminhamento_id']);
    }

    public function testRejeitaAtendimentoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Vinc T A']);
        $m2 = $this->criarMulher(['nome_completo' => 'Vinc T B']);
        $atendimentoId = $this->criarAtendimento($m1['id']);
        $this->logarComo('admin');

        $result = $this->post('tarefas', [
            'titulo'         => 'Vincular errado',
            'mulher_id'      => $m2['id'],
            'atendimento_id' => $atendimentoId,
            'responsavel_id' => 1,
            'prioridade'     => 'media',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testTarefaPodeSerSemVincular(): void
    {
        $this->logarComo('admin');

        $result = $this->post('tarefas', [
            'titulo'         => 'Organizar documentos internos',
            'responsavel_id' => 1,
            'prioridade'     => 'baixa',
        ]);

        $result->assertStatus(302);

        $tarefa = (new TarefaModel())->orderBy('id', 'DESC')->first();
        $this->assertSame('pendente', $tarefa['status']);
        $this->assertNull($tarefa['mulher_id']);
    }

    public function testTodasListaTodasAsSituacoes(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Todas Conc']);
        $this->criarTarefa(['titulo' => 'Concluir processo interno', 'mulher_id' => $m1['id'], 'status' => 'concluida', 'data_conclusao' => date('Y-m-d H:i:s')]);

        $m2 = $this->criarMulher(['nome_completo' => 'Todas Pend']);
        $this->criarTarefa(['titulo' => 'Contatar a assistida', 'mulher_id' => $m2['id']]);
        $this->logarComo('admin');

        $result = $this->get('tarefas');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Contatar a assistida', $body);
        $this->assertStringContainsString('Concluir processo interno', $body);
        $this->assertStringContainsString('Em andamento', $body);
    }

    public function testVisaoPendentesFiltra(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Pend Visible']);
        $this->criarTarefa(['titulo' => 'Tarefa pendente visivel', 'mulher_id' => $m1['id']]);

        $m2 = $this->criarMulher(['nome_completo' => 'Conc Hidden']);
        $this->criarTarefa(['titulo' => 'Tarefa concluida oculta', 'mulher_id' => $m2['id'], 'status' => 'concluida', 'data_conclusao' => date('Y-m-d H:i:s')]);
        $this->logarComo('admin');

        $result = $this->get('tarefas?visao=pendentes');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa pendente visivel', $body);
        $this->assertStringNotContainsString('Tarefa concluida oculta', $body);
    }

    public function testVisaoEmAndamentoFiltra(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'And Visible']);
        $this->criarTarefa(['titulo' => 'Tarefa em andamento visivel', 'mulher_id' => $m1['id'], 'status' => 'em_andamento']);

        $m2 = $this->criarMulher(['nome_completo' => 'Pend Hidden']);
        $this->criarTarefa(['titulo' => 'Tarefa pendente oculta', 'mulher_id' => $m2['id']]);
        $this->logarComo('admin');

        $result = $this->get('tarefas?visao=em_andamento');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa em andamento visivel', $body);
        $this->assertStringNotContainsString('Tarefa pendente oculta', $body);
    }

    public function testVisaoConcluidasFiltra(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Conc Visible']);
        $this->criarTarefa(['titulo' => 'Tarefa concluida visivel', 'mulher_id' => $m1['id'], 'status' => 'concluida', 'data_conclusao' => date('Y-m-d H:i:s')]);

        $m2 = $this->criarMulher(['nome_completo' => 'Pend Hidden']);
        $this->criarTarefa(['titulo' => 'Tarefa pendente oculta', 'mulher_id' => $m2['id']]);
        $this->logarComo('admin');

        $result = $this->get('tarefas?visao=concluidas');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa concluida visivel', $body);
        $this->assertStringNotContainsString('Tarefa pendente oculta', $body);
    }

    public function testVisaoAtrasadasComparaPrazoComSituacao(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Atrasada Visivel']);
        $this->criarTarefa(['titulo' => 'Tarefa atrasada pendente', 'mulher_id' => $m1['id'], 'prazo' => '2026-01-05']);

        $m2 = $this->criarMulher(['nome_completo' => 'NoPrazo Oculto']);
        $this->criarTarefa(['titulo' => 'Tarefa no prazo', 'mulher_id' => $m2['id'], 'prazo' => date('Y-m-d', strtotime('+30 days'))]);

        $m3 = $this->criarMulher(['nome_completo' => 'Atrasada Conc']);
        $this->criarTarefa(['titulo' => 'Tarefa antiga concluida', 'mulher_id' => $m3['id'], 'prazo' => '2026-01-05', 'status' => 'concluida', 'data_conclusao' => '2026-01-01 10:00:00']);
        $this->logarComo('admin');

        $result = $this->get('tarefas?visao=atrasadas');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa atrasada pendente', $body);
        $this->assertStringContainsString('Atrasada', $body);
        $this->assertStringNotContainsString('Tarefa no prazo', $body);
        $this->assertStringNotContainsString('Tarefa antiga concluida', $body);
    }

    public function testFluxoDeStatusAteConcluidaMantemRegistro(): void
    {
        $t = $this->criarTarefa();
        $this->logarComo('admin');

        $model = new TarefaModel();

        $fluxo = ['em_andamento', 'concluida'];
        foreach ($fluxo as $status) {
            $result = $this->post('tarefas/' . $t['id'] . '/status', ['status' => $status]);
            $result->assertRedirectTo('/tarefas/' . $t['id']);
        }

        $atual = $model->find($t['id']);
        $this->assertSame('concluida', $atual['status']);
        $this->assertNotNull($atual['data_conclusao']);

        // O registro NÃO é apagado: continua na base para consulta.
        $this->assertSame(1, $model->where('id', $t['id'])->countAllResults());
    }

    public function testCancelarTarefa(): void
    {
        $t = $this->criarTarefa();
        $this->logarComo('admin');

        $result = $this->post('tarefas/' . $t['id'] . '/status', ['status' => 'cancelada']);

        $result->assertRedirectTo('/tarefas/' . $t['id']);
        $this->assertSame('cancelada', (new TarefaModel())->find($t['id'])['status']);
        $this->assertNull((new TarefaModel())->find($t['id'])['data_conclusao']);
    }

    public function testTransicaoInvalidaNaoPermitida(): void
    {
        $t = $this->criarTarefa();
        $this->logarComo('admin');

        // Pendente não pode saltar direto para Concluída.
        $result = $this->post('tarefas/' . $t['id'] . '/status', ['status' => 'concluida']);

        $result->assertSessionHas('erro');
        $this->assertSame('pendente', (new TarefaModel())->find($t['id'])['status']);
        $this->assertNull((new TarefaModel())->find($t['id'])['data_conclusao']);
    }

    public function testStatusConcluidaNaoAceitaNovasTransicoes(): void
    {
        $t = $this->criarTarefa(['status' => 'concluida', 'data_conclusao' => date('Y-m-d H:i:s')]);
        $this->logarComo('admin');

        $result = $this->post('tarefas/' . $t['id'] . '/status', ['status' => 'em_andamento']);

        $result->assertSessionHas('erro');
        $this->assertSame('concluida', (new TarefaModel())->find($t['id'])['status']);
    }

    public function testDetalhesExibeCamposEStatus(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Ficha Tarefa']);
        $t = $this->criarTarefa([
            'titulo'         => 'Atualizar situacao do caso',
            'descricao'      => 'Detalhes internos',
            'mulher_id'      => $m['id'],
            'responsavel_id' => 1,
            'prazo'          => '2026-06-25',
            'prioridade'     => 'alta',
        ]);
        $this->logarComo('admin');

        $result = $this->get('tarefas/' . $t['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa #' . $t['id'], $body);
        $this->assertStringContainsString('Atualizar situacao do caso', $body);
        $this->assertStringContainsString('Ficha Tarefa', $body);
        $this->assertStringContainsString('25/06/2026', $body);
        $this->assertStringContainsString('Alta', $body);
        $this->assertStringContainsString('Pendente', $body);
        $this->assertStringContainsString('Em andamento', $body);
        $this->assertStringContainsString('Editar tarefa', $body);
    }

    public function testAtualizarTarefaConcluiEDataConclusao() : void
    {
        $t = $this->criarTarefa();
        $this->logarComo('admin');

        $result = $this->post('tarefas/atualizar/' . $t['id'], [
            'titulo'         => 'Titulo editado',
            'responsavel_id' => 1,
            'prioridade'     => 'baixa',
            'status'         => 'concluida',
        ]);

        $result->assertRedirectTo('/tarefas/' . $t['id']);

        $atual = (new TarefaModel())->find($t['id']);
        $this->assertSame('Titulo editado', $atual['titulo']);
        $this->assertSame('concluida', $atual['status']);
        $this->assertNotNull($atual['data_conclusao']);
    }

    public function testFiltroPorPrioridade(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Prioridade Alta']);
        $this->criarTarefa(['titulo' => 'Tarefa alta', 'mulher_id' => $m1['id'], 'prioridade' => 'alta']);

        $m2 = $this->criarMulher(['nome_completo' => 'Prioridade Baixa']);
        $this->criarTarefa(['titulo' => 'Tarefa baixa', 'mulher_id' => $m2['id'], 'prioridade' => 'baixa']);
        $this->logarComo('admin');

        $result = $this->get('tarefas?prioridade=alta');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa alta', $body);
        $this->assertStringNotContainsString('Tarefa baixa', $body);
    }

    public function testFiltroPorStatus(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Status Cancelada']);
        $this->criarTarefa(['titulo' => 'Tarefa cancelada', 'mulher_id' => $m1['id'], 'status' => 'cancelada']);

        $m2 = $this->criarMulher(['nome_completo' => 'Status Pendente']);
        $this->criarTarefa(['titulo' => 'Tarefa pendente', 'mulher_id' => $m2['id']]);
        $this->logarComo('admin');

        $result = $this->get('tarefas?status=cancelada');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa cancelada', $body);
        $this->assertStringNotContainsString('Tarefa pendente', $body);
    }

    public function testFiltroPorPeriodoDePrazo(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Prazo Dentro']);
        $this->criarTarefa(['titulo' => 'Tarefa dentro do periodo', 'mulher_id' => $m1['id'], 'prazo' => '2026-06-15']);

        $m2 = $this->criarMulher(['nome_completo' => 'Prazo Fora']);
        $this->criarTarefa(['titulo' => 'Tarefa fora do periodo', 'mulher_id' => $m2['id'], 'prazo' => '2027-01-01']);
        $this->logarComo('admin');

        $result = $this->get('tarefas?data_de=2026-06-01&data_ate=2026-06-30');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa dentro do periodo', $body);
        $this->assertStringNotContainsString('Tarefa fora do periodo', $body);
    }

    public function testFiltroPorResponsavel(): void
    {
        $segundo = (new UserModel())->insert([
            'nome'   => 'Segunda Referencia',
            'email'  => 'segunda@teste.com',
            'senha'  => 'senha123',
            'perfil' => 'funcionario',
            'ativo'  => 1,
        ]);
        $this->assertIsInt($segundo);

        $m1 = $this->criarMulher(['nome_completo' => 'Resp Um']);
        $this->criarTarefa(['titulo' => 'Tarefa do responsavel 1', 'mulher_id' => $m1['id'], 'responsavel_id' => 1]);

        $m2 = $this->criarMulher(['nome_completo' => 'Resp Dois']);
        $this->criarTarefa(['titulo' => 'Tarefa do responsavel 2', 'mulher_id' => $m2['id'], 'responsavel_id' => $segundo]);
        $this->logarComo('admin');

        $result = $this->get('tarefas?responsavel=1');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Tarefa do responsavel 1', $body);
        $this->assertStringNotContainsString('Tarefa do responsavel 2', $body);
    }

    public function testPaginaNovaCarregaComMulherPreselecionada(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Tarefa']);
        $this->logarComo('admin');

        $result = $this->get('tarefas/nova?mulher_id=' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Nova Tarefa', $body);
        $this->assertStringContainsString('Preselecao Tarefa', $body);
        $this->assertStringContainsString('value="' . $m['id'] . '"', $body);
    }

    public function testPaginaNovaCarregaComEncaminhamentoVinculado(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Tarefa Enc']);
        $encaminhamentoId = $this->criarEncaminhamento($m['id']);
        $this->logarComo('admin');

        $result = $this->get('tarefas/nova?encaminhamento_id=' . $encaminhamentoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Preselecao Tarefa Enc', $body);
        $this->assertStringContainsString('value="' . $encaminhamentoId . '"', $body);
    }
}