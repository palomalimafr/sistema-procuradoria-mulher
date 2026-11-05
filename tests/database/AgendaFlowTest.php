<?php

use App\Models\AtendimentoModel;
use App\Models\CompromissoModel;
use App\Models\EncaminhamentoModel;
use App\Models\MulherModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo AGENDA: compromissos vinculados a mulher/atendimento/
 * encaminhamento, visualizações dia/semana/mês sobre os mesmos registros,
 * filtros, fluxo de status e reagendamento rastreável.
 *
 * @internal
 */
final class AgendaFlowTest extends CIUnitTestCase
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
            'nome_completo'   => 'Mulher Agenda',
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

    private function criarCompromisso(array $extra = []): array
    {
        $model = new CompromissoModel();
        $dados = array_merge([
            'tipo'        => 'retorno',
            'data'        => date('Y-m-d'),
            'hora_inicio' => '10:30:00',
            'status'      => 'agendado',
        ], $extra);

        $id = $model->insert($dados);
        $this->assertIsInt($id);

        return $model->find($id);
    }

    public function testVisitanteNaoAcessaAgenda(): void
    {
        $agenda = $this->get('agenda');
        $agenda->assertRedirectTo('/login');

        $novo = $this->get('agenda/novo');
        $novo->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaAgenda(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('agenda');
        $result->assertRedirectTo('/dashboard');
    }

    public function testNovoCompromissoExigeCamposObrigatorios(): void
    {
        $this->logarComo('admin');

        $result = $this->post('agenda', [
            'tipo' => '',
            'data' => '',
            'hora_inicio' => '',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testCriarCompromissoRegistraAgendado(): void
    {
        $this->logarComo('admin');

        $result = $this->post('agenda', [
            'tipo'            => 'retorno',
            'data'            => '2026-06-01',
            'hora_inicio'     => '09:00',
            'hora_fim'        => '09:30',
            'profissional_id' => 1,
            'observacoes'     => 'Retorno quinzenal',
        ]);

        $result->assertStatus(302);
        $this->assertStringContainsString('/agenda/', $result->getRedirectUrl());

        $compromisso = (new CompromissoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($compromisso);
        $this->assertSame('agendado', $compromisso['status']);
        $this->assertSame('retorno', $compromisso['tipo']);
        $this->assertSame('2026-06-01', $compromisso['data']);
        $this->assertSame('09:00:00', $compromisso['hora_inicio']);
        $this->assertSame('09:30:00', $compromisso['hora_fim']);
    }

    public function testCriarCompromissoVinculado(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Ana Agenda']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $encaminhamentoId = $this->criarEncaminhamento($m['id']);
        $this->logarComo('admin');

        $result = $this->post('agenda', [
            'mulher_id'         => $m['id'],
            'atendimento_id'    => $atendimentoId,
            'encaminhamento_id' => $encaminhamentoId,
            'tipo'              => 'atendimento_psicologico',
            'data'              => '2026-06-03',
            'hora_inicio'       => '14:00',
            'profissional_id'   => 1,
        ]);

        $result->assertStatus(302);

        $compromisso = (new CompromissoModel())->orderBy('id', 'DESC')->first();
        $this->assertSame((int) $m['id'], (int) $compromisso['mulher_id']);
        $this->assertSame($atendimentoId, (int) $compromisso['atendimento_id']);
        $this->assertSame($encaminhamentoId, (int) $compromisso['encaminhamento_id']);
    }

    public function testRejeitaAtendimentoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Vinc A']);
        $m2 = $this->criarMulher(['nome_completo' => 'Vinc B']);
        $atendimentoId = $this->criarAtendimento($m1['id']);
        $this->logarComo('admin');

        $result = $this->post('agenda', [
            'mulher_id'         => $m2['id'],
            'atendimento_id'    => $atendimentoId,
            'tipo'              => 'retorno',
            'data'              => '2026-06-01',
            'hora_inicio'       => '09:00',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testRejeitaEncaminhamentoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Vinc C']);
        $m2 = $this->criarMulher(['nome_completo' => 'Vinc D']);
        $encaminhamentoId = $this->criarEncaminhamento($m1['id']);
        $this->logarComo('admin');

        $result = $this->post('agenda', [
            'mulher_id'         => $m2['id'],
            'encaminhamento_id' => $encaminhamentoId,
            'tipo'              => 'retorno',
            'data'              => '2026-06-01',
            'hora_inicio'       => '09:00',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
    }

    public function testCompromissoPodeSerSemMulher(): void
    {
        $this->logarComo('admin');

        $result = $this->post('agenda', [
            'tipo'            => 'reuniao',
            'data'            => '2026-06-05',
            'hora_inicio'     => '08:00',
            'profissional_id' => 1,
        ]);

        $result->assertStatus(302);

        $compromisso = (new CompromissoModel())->orderBy('id', 'DESC')->first();
        $this->assertSame('reuniao', $compromisso['tipo']);
        $this->assertNull($compromisso['mulher_id']);
    }

    public function testDiaSemanaMesUsamOsMesmosRegistros(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Calendario Agenda']);
        $this->criarCompromisso([
            'mulher_id' => $m['id'],
            'tipo'      => 'atendimento_social',
            'data'      => '2026-06-03',
            'hora_inicio' => '10:00:00',
        ]);
        $this->logarComo('admin');

        $dia = $this->get('agenda?visao=dia&data=2026-06-03');
        $dia->assertStatus(200);
        $this->assertStringContainsString('Calendario Agenda', $dia->getBody());

        $semana = $this->get('agenda?visao=semana&data=2026-06-03');
        $semana->assertStatus(200);
        $this->assertStringContainsString('Calendario Agenda', $semana->getBody());

        $mes = $this->get('agenda?visao=mes&data=2026-06-03');
        $mes->assertStatus(200);
        $this->assertStringContainsString('Calendario Agenda', $mes->getBody());
    }

    public function testAgendaDiaExibeCompromissoForaDaLinhaDaSemana(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Dia Especifico']);
        $this->criarCompromisso([
            'mulher_id' => $m['id'],
            'data'      => '2026-08-12',
        ]);
        $this->logarComo('admin');

        $result = $this->get('agenda?visao=dia&data=2026-08-12');
        $result->assertStatus(200);
        $this->assertStringContainsString('Dia Especifico', $result->getBody());

        $fora = $this->get('agenda?visao=dia&data=2026-08-13');
        $this->assertStringNotContainsString('Dia Especifico', $fora->getBody());
    }

    public function testDetalhesExibeCamposEStatus(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Ficha Agenda']);
        $c = $this->criarCompromisso([
            'mulher_id'      => $m['id'],
            'tipo'           => 'atendimento_juridico',
            'data'           => '2026-06-10',
            'hora_inicio'    => '13:00:00',
            'hora_fim'       => '14:00:00',
            'profissional_id' => 1,
            'observacoes'    => 'Analisar documentacao',
        ]);
        $this->logarComo('admin');

        $result = $this->get('agenda/' . $c['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Compromisso #' . $c['id'], $body);
        $this->assertStringContainsString('Ficha Agenda', $body);
        $this->assertStringContainsString('jur', $body);
        $this->assertStringContainsString('10/06/2026', $body);
        $this->assertStringContainsString('Analisar documentacao', $body);
        $this->assertStringContainsString('Agendado', $body);
        $this->assertStringContainsString('Reagendar compromisso', $body);
    }

    public function testFluxoDeStatusAteRealizado(): void
    {
        $c = $this->criarCompromisso();
        $this->logarComo('admin');

        $fluxo = ['confirmado', 'realizado'];
        foreach ($fluxo as $status) {
            $result = $this->post('agenda/' . $c['id'] . '/status', ['status' => $status]);
            $result->assertRedirectTo('/agenda/' . $c['id']);
        }

        $this->assertSame('realizado', (new CompromissoModel())->find($c['id'])['status']);
    }

    public function testCancelarCompromisso(): void
    {
        $c = $this->criarCompromisso();
        $this->logarComo('admin');

        $result = $this->post('agenda/' . $c['id'] . '/status', ['status' => 'cancelado']);

        $result->assertRedirectTo('/agenda/' . $c['id']);
        $this->assertSame('cancelado', (new CompromissoModel())->find($c['id'])['status']);
    }

    public function testTransicaoInvalidaNaoPermitida(): void
    {
        $c = $this->criarCompromisso();
        $this->logarComo('admin');

        // Agendado não pode saltar direto para Realizado.
        $result = $this->post('agenda/' . $c['id'] . '/status', ['status' => 'realizado']);

        $result->assertSessionHas('erro');
        $this->assertSame('agendado', (new CompromissoModel())->find($c['id'])['status']);
    }

    public function testReagendamentoCriaNovoEConectaOriginal(): void
    {
        $m = $this->criarMulher();
        $c = $this->criarCompromisso([
            'mulher_id'   => $m['id'],
            'tipo'        => 'retorno',
            'data'        => '2026-06-01',
            'hora_inicio' => '10:00:00',
        ]);
        $this->logarComo('admin');

        $result = $this->post('agenda/' . $c['id'] . '/reagendar', [
            'data'        => '2026-06-15',
            'hora_inicio' => '16:00',
        ]);

        $result->assertRedirectTo('/agenda/' . $c['id']);

        $atual = (new CompromissoModel())->find($c['id']);
        $this->assertSame('reagendado', $atual['status']);
        $this->assertNotNull($atual['reagendado_para_id']);

        $novo = (new CompromissoModel())->find($atual['reagendado_para_id']);
        $this->assertNotNull($novo);
        $this->assertSame('agendado', $novo['status']);
        $this->assertSame('2026-06-15', $novo['data']);
        $this->assertSame('16:00:00', $novo['hora_inicio']);
        $this->assertSame((int) $m['id'], (int) $novo['mulher_id']);
        $this->assertSame('retorno', $novo['tipo']);
    }

    public function testReagendamentoNaoDuplicaOOriginal(): void
    {
        $c = $this->criarCompromisso();
        $this->logarComo('admin');

        $this->post('agenda/' . $c['id'] . '/reagendar', [
            'data'        => '2026-07-02',
            'hora_inicio' => '09:00',
        ]);

        $total = (new CompromissoModel())->where('tipo', 'retorno')->countAllResults();

        $this->assertSame(2, $total);
    }

    public function testReagendamentoNaoPermitidoParaRealizado(): void
    {
        $c = $this->criarCompromisso(['status' => 'realizado']);
        $this->logarComo('admin');

        $result = $this->post('agenda/' . $c['id'] . '/reagendar', [
            'data'        => '2026-07-02',
            'hora_inicio' => '09:00',
        ]);

        $result->assertSessionHas('erro');
        $this->assertSame('realizado', (new CompromissoModel())->find($c['id'])['status']);
    }

    public function testFiltroPorTipo(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Filtro Reuniao']);
        $this->criarCompromisso([
            'mulher_id'      => $m1['id'],
            'tipo'           => 'reuniao',
            'data'           => '2026-06-11',
            'hora_inicio'    => '08:00:00',
        ]);

        $m2 = $this->criarMulher(['nome_completo' => 'Filtro Visita']);
        $this->criarCompromisso([
            'mulher_id'      => $m2['id'],
            'tipo'           => 'visita',
            'data'           => '2026-06-11',
            'hora_inicio'    => '11:00:00',
        ]);
        $this->logarComo('admin');

        $result = $this->get('agenda?visao=dia&data=2026-06-11&tipo=reuniao');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Filtro Reuniao', $body);
        $this->assertStringNotContainsString('Filtro Visita', $body);
    }

    public function testFiltroPorStatus(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Status Cancelado']);
        $this->criarCompromisso([
            'mulher_id'   => $m1['id'],
            'data'        => '2026-06-20',
            'hora_inicio' => '09:00:00',
            'status'      => 'cancelado',
        ]);

        $m2 = $this->criarMulher(['nome_completo' => 'Status Agendado']);
        $this->criarCompromisso([
            'mulher_id'   => $m2['id'],
            'data'        => '2026-06-20',
            'hora_inicio' => '10:00:00',
            'status'      => 'agendado',
        ]);
        $this->logarComo('admin');

        $result = $this->get('agenda?visao=dia&data=2026-06-20&status=cancelado');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Status Cancelado', $body);
        $this->assertStringNotContainsString('Status Agendado', $body);
    }

    public function testBuscarEncaminhamentosRetornaJSON(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Agenda Enc']);
        $this->criarEncaminhamento($m['id']);
        $this->logarComo('admin');

        $result = $this->get('agenda/buscar-encaminhamentos?term=Agenda');

        $result->assertStatus(200);
        $dados = json_decode((string) $result->getJSON(), true);
        $this->assertArrayHasKey('encaminhamentos', $dados);
        $this->assertNotEmpty($dados['encaminhamentos']);
        $this->assertSame($m['id'], (int) $dados['encaminhamentos'][0]['mulher_id']);
    }

    public function testPaginaNovoCarregaComMulherPreselecionada(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Agenda']);
        $this->logarComo('admin');

        $result = $this->get('agenda/novo?mulher_id=' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Novo Compromisso', $body);
        $this->assertStringContainsString('Preselecao Agenda', $body);
        $this->assertStringContainsString('value="' . $m['id'] . '"', $body);
    }

    public function testPaginaNovoCarregaComEncaminhamentoVinculado(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Enc']);
        $encaminhamentoId = $this->criarEncaminhamento($m['id']);
        $this->logarComo('admin');

        $result = $this->get('agenda/novo?encaminhamento_id=' . $encaminhamentoId);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Preselecao Enc', $body);
        $this->assertStringContainsString('value="' . $encaminhamentoId . '"', $body);
    }
}