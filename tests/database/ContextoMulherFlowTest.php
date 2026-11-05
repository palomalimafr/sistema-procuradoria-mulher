<?php

use App\Models\AcolhimentoModel;
use App\Models\AtendimentoModel;
use App\Models\CasoModel;
use App\Models\EncaminhamentoModel;
use App\Models\MedidaProtetivaModel;
use App\Models\MulherModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes da contextualização: quando a criação de uma ação parte da ficha da
 * mulher, o formulário já sabe quem é a mulher, traz o contexto/histórico
 * para consulta e não exige nova busca.
 *
 * Regras estruturais verificadas aqui:
 *  - o caminho que começa pelo módulo continua funcionando igual;
 *  - o atendimento só é pré-selecionado quando é identificável com segurança
 *    (exatamente um em aberto) — nunca por inferência;
 *  - nada do histórico é copiado para o registro novo.
 *
 * @internal
 */
final class ContextoMulherFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    private function logarComoEquipe(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Equipe Teste',
            'usuario_email' => 'equipe@teste.com',
            'perfil'        => 'funcionario',
        ]);
    }

    private function corpo(TestResponse $resultado): string
    {
        return html_entity_decode($resultado->getBody(), ENT_QUOTES, 'UTF-8');
    }

    private function criarMulher(string $nome = 'Maria Contexto'): int
    {
        $id = (new MulherModel())->insert([
            'nome_completo'           => $nome,
            'cpf'                     => '12345678901',
            'data_nascimento'         => '1990-05-10',
            'zona'                    => 'urbana',
            'bairro'                  => 'Centro',
            'municipio'               => 'Campos dos Goytacazes',
            'situacao_vulnerabilidade' => 1,
        ]);
        $this->assertIsInt($id);

        return $id;
    }

    /**
     * @param string[] $situacoes Situações que o atendimento deve ter; por
     *                            padrão apenas um atendimento em aberto.
     */
    private function criarAtendimentos(int $mulherId, array $situacoes = ['em_andamento'], string $prefixo = 'AT'): array
    {
        $ids = [];
        $n = 0;

        foreach ($situacoes as $situacao) {
            $n++;
            $id = (new AtendimentoModel())->insert([
                'protocolo'           => sprintf('%s-%d-%06d', $prefixo, 26, $n),
                'data_hora'           => sprintf('2026-0%d-10 09:00:00', min(9, $n)),
                'mulher_id'           => $mulherId,
                'profissional_id'      => 1,
                'forma_atendimento'   => 'presencial',
                'origem'              => 'demanda_espontanea',
                'prioridade'          => 'media',
                'situacao'            => $situacao,
            ]);
            $this->assertIsInt($id);
            $ids[] = $id;
        }

        return $ids;
    }

    private function dadosMedida(array $extra = []): array
    {
        return array_merge([
            'possui_medida'    => 1,
            'numero'           => 'MP-2026-001',
            'data_solicitacao' => '2026-03-01',
            'data_concessao'   => '2026-03-05',
            'orgao'            => 'poder_judiciario',
            'situacao'         => 'solicitada',
        ], $extra);
    }

    /* ================================================================
     * Medida protetiva a partir da ficha
     * ================================================================ */

    public function testMedidaPelaFichaJaVemComMulherEAtenendimentoUnico(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $atendimentoId = $this->criarAtendimentos($mulherId)[0];

        $body = $this->corpo($this->get('medidas-protetivas/nova?mulher_id=' . $mulherId));

        // Mulher já vinculada: não é preciso buscá-la de novo.
        $this->assertStringContainsString('name="mulher_id" id="mulher_id" value="' . $mulherId . '"', $body);
        $this->assertStringContainsString('Maria Contexto', $body);

        // Exatamente um atendimento em aberto: pré-selecionado com segurança.
        $this->assertStringContainsString('name="atendimento_id" id="atendimento_id" value="' . $atendimentoId . '"', $body);
        $this->assertStringContainsString('AT-26-000001', $body);

        // Contexto disponível durante o preenchimento.
        $this->assertStringContainsString('Atendimento em aberto', $body);
        $this->assertStringContainsString('Ver histórico', $body);
    }

    public function testMedidaPelaFichaNaoPreSelecionaAtendimentoQuandoNaoHaEmAberto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher('Sem Aberto');
        $this->criarAtendimentos($mulherId, ['encerrado'], 'AT');

        $body = $this->corpo($this->get('medidas-protetivas/nova?mulher_id=' . $mulherId));

        $this->assertStringContainsString('value="' . $mulherId . '"', $body);
        $this->assertStringContainsString('name="atendimento_id" id="atendimento_id" value=""', $body);
        $this->assertStringNotContainsString('Atendimento em aberto', $body);
    }

    public function testMedidaPelaFichaNaoPreSelecionaAtendimentoQuandoHaMaisDeUmEmAberto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher('Dois Abertos');
        $this->criarAtendimentos($mulherId, ['em_andamento', 'aguardando_atendimento'], 'AT');

        $body = $this->corpo($this->get('medidas-protetivas/nova?mulher_id=' . $mulherId));

        // Ambiguidade: nada é inferido, a escolha fica com a profissional.
        $this->assertStringContainsString('name="atendimento_id" id="atendimento_id" value=""', $body);
        $this->assertStringNotContainsString('Atendimento em aberto', $body);
    }

    public function testContextoMostraOsRegistrosAnterioresDaMulher(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $this->criarAtendimentos($mulherId);

        $encaminhamentoId = (new EncaminhamentoModel())->insert([
            'data_hora'  => '2026-02-01 10:00:00',
            'mulher_id'  => $mulherId,
            'status'     => 'pendente',
            'orgao'      => 'CREAS Municipal',
            'prioridade' => 'alta',
        ]);
        $this->assertIsInt($encaminhamentoId);

        $body = $this->corpo($this->get('medidas-protetivas/nova?mulher_id=' . $mulherId));

        $this->assertStringContainsString('Encaminhamento para CREAS Municipal', $body);
        $this->assertStringContainsString('1 Encaminhamento', $body);
        $this->assertStringContainsString('mulheres/historico/' . $mulherId, $body);
        $this->assertStringContainsString('encaminhamentos/' . $encaminhamentoId, $body);
    }

    /* ================================================================
     * Medida protetiva: atendimento obrigatório na criação
     * ================================================================ */

    public function testCriarMedidaSemAtendimentoEhRejeitado(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $this->criarAtendimentos($mulherId);

        $result = $this->post('medidas-protetivas', $this->dadosMedida(['mulher_id' => $mulherId, 'atendimento_id' => '']));

        $result->assertRedirect();
        if ((new MedidaProtetivaModel())->where('mulher_id', $mulherId)->first() !== null) { $this->fail('A medida nao deveria ter sido gravada.'); }
        $this->assertStringContainsString('atendimento de origem', strtolower($this->corpo($this->get('medidas-protetivas/nova?mulher_id=' . $mulherId))));
    }

    public function testCriarMedidaRejeitaAtendimentoDeOutraMulher(): void
    {
        $this->logarComoEquipe();
        $mulherA = $this->criarMulher('Mulher A');
        $mulherB = $this->criarMulher('Mulher B');
        $atendimentoB = $this->criarAtendimentos($mulherB)[0];

        $result = $this->post('medidas-protetivas', $this->dadosMedida([
            'mulher_id'      => $mulherA,
            'atendimento_id' => $atendimentoB,
        ]));

        $result->assertRedirect();
        if ((new MedidaProtetivaModel())->where('mulher_id', $mulherA)->first() !== null) { $this->fail('A medida nao deveria ter sido gravada.'); }
    }

    public function testCriarMedidaDaFichaGravaOsVinculos(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $atendimentoId = $this->criarAtendimentos($mulherId)[0];

        $result = $this->post('medidas-protetivas', $this->dadosMedida([
            'mulher_id'      => $mulherId,
            'atendimento_id' => $atendimentoId,
        ]));

        $result->assertRedirect();
        $medida = (new MedidaProtetivaModel())->where('mulher_id', $mulherId)->first();
        $this->assertIsArray($medida);
        $this->assertSame($atendimentoId, (int) $medida['atendimento_id']);
        $this->assertSame(1, (int) $medida['possui_medida']);
    }

    /* ================================================================
     * Medida protetiva: não-regressão do caminho pelo módulo
     * ================================================================ */

    public function testMedidaPeloModuloContinuaPermitindoEscolherAMulher(): void
    {
        $this->logarComoEquipe();

        $result = $this->get('medidas-protetivas/nova');
        $result->assertStatus(200);

        $body = $this->corpo($result);
        // Sem contexto: nada foi pré-selecionado e a busca segue disponível.
        $this->assertStringContainsString('name="mulher_id" id="mulher_id" value=""', $body);
        $this->assertStringContainsString('atendimentos/buscar-mulheres', $body);
        $this->assertStringContainsString('Selecione a mulher primeiro', $body);
        $this->assertStringNotContainsString('Ver histórico', $body);
    }

    public function testMedidaLegadaSemAtendimentoContinuaEditavel(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher('Medida Legada');
        $this->criarAtendimentos($mulherId, ['encerrado'], 'AT');

        $medidaId = (new MedidaProtetivaModel())->insert([
            'mulher_id'     => $mulherId,
            'atendimento_id' => null,
            'possui_medida' => 1,
            'situacao'      => 'concedida',
        ]);
        $this->assertIsInt($medidaId);

        // A edição não pode exigir um atendimento que o registro legado não tem.
        $result = $this->post('medidas-protetivas/atualizar/' . $medidaId, $this->dadosMedida([
            'mulher_id'      => $mulherId,
            'atendimento_id' => '',
            'situacao'       => 'concedida',
        ]));

        $result->assertRedirect();
        if ((new MedidaProtetivaModel())->find($medidaId) === null) { $this->fail('A medida legada foi apagada.'); }
    }

    public function testEditarMedidaNaoExibeOPainelDeContexto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $atendimentoId = $this->criarAtendimentos($mulherId)[0];

        $medidaId = (new MedidaProtetivaModel())->insert([
            'mulher_id'      => $mulherId,
            'atendimento_id' => $atendimentoId,
            'possui_medida'  => 1,
            'situacao'       => 'solicitada',
        ]);

        $body = $this->corpo($this->get('medidas-protetivas/editar/' . $medidaId));
        $this->assertStringNotContainsString('Ver histórico', $body);
        $this->assertStringContainsString('Maria Contexto', $body);
    }

    /* ================================================================
     * Endpoint de busca de atendimentos
     * ================================================================ */

    public function testBuscaDeAtendimentosFiltraPelaMulher(): void
    {
        $this->logarComoEquipe();
        $mulherA = $this->criarMulher('Alvo Busca');
        $mulherB = $this->criarMulher('Outra Busca');
        $idA = $this->criarAtendimentos($mulherA, ['em_andamento'], 'AAA')[0];
        $this->criarAtendimentos($mulherB, ['em_andamento'], 'BBB');

        $result = $this->get('atendimentos/buscar?term=&mulher_id=' . $mulherA);
        $result->assertStatus(200);

        $json = json_decode($result->getJSON(), true);
        $this->assertIsArray($json);
        $this->assertCount(1, $json['atendimentos']);
        $this->assertSame($idA, $json['atendimentos'][0]['id']);
        $this->assertSame($mulherA, $json['atendimentos'][0]['mulher_id']);
    }

    public function testBuscaDeAtendimentosExigePerfilDaEquipe(): void
    {
        $this->withSession([
            'logado'        => true,
            'usuario_id'    => 1,
            'usuario_nome'  => 'Mulher Teste',
            'usuario_email' => 'mulher@teste.com',
            'perfil'        => 'mulher',
        ]);

        $result = $this->get('atendimentos/buscar?termo=x');
        $result->assertRedirect();
    }

    /* ================================================================
     * Contexto nunca aparece sem mulher válida
     * ================================================================ */

    public function testContextoNaoApareceComMulherInexistente(): void
    {
        $this->logarComoEquipe();

        $body = $this->corpo($this->get('medidas-protetivas/nova?mulher_id=999999'));

        $this->assertStringNotContainsString('Ver histórico', $body);
        $this->assertStringContainsString('name="mulher_id" id="mulher_id" value=""', $body);
    }

    public function testContextoNaoApareceComMulherZero(): void
    {
        $this->logarComoEquipe();

        $body = $this->corpo($this->get('medidas-protetivas/nova?mulher_id=0'));

        $this->assertStringNotContainsString('Ver histórico', $body);
    }

    /* ================================================================
     | Acolhimento iniciado pela ficha
     * ================================================================ */

    public function testAcolhimentoPelaFichaJaVemComOAtendimentoUnicoEmAberto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $ids = $this->criarAtendimentos($mulherId, ['em_andamento'], 'AC');

        $body = $this->corpo($this->get('acolhimentos/novo?mulher_id=' . $mulherId));

        $this->assertStringContainsString('value="' . $ids[0] . '"', $body);
        $this->assertStringContainsString('Ver histórico', $body);
    }

    public function testAcolhimentoPelaFichaAvisaQuandoJaExisteAcolhimentoEmAberto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $ids = $this->criarAtendimentos($mulherId, ['em_andamento'], 'AC');

        $acolhimentoId = (new AcolhimentoModel())->insert([
            'atendimento_id' => $ids[0],
            'data_hora'      => '2026-03-01 09:00:00',
            'status'         => 'em_andamento',
            'motivo_procura' => 'Primeira retirada',
        ]);
        $this->assertIsInt($acolhimentoId);

        $body = $this->corpo($this->get('acolhimentos/novo?mulher_id=' . $mulherId));

        $this->assertStringContainsString('acolhimento em aberto', $body);
        $this->assertStringContainsString('acolhimentos/' . $acolhimentoId, $body);
    }

    public function testAcolhimentoNaoAvisaQuandoOAnteriorEstaConcluido(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $ids = $this->criarAtendimentos($mulherId, ['em_andamento'], 'AC');

        $acolhimentoId = (new AcolhimentoModel())->insert([
            'atendimento_id'  => $ids[0],
            'data_hora'       => '2026-03-01 09:00:00',
            'status'          => 'concluido',
            'data_conclusao'  => '2026-03-05 09:00:00',
            'motivo_procura'  => 'Primeira retirada',
        ]);
        $this->assertIsInt($acolhimentoId);

        $body = $this->corpo($this->get('acolhimentos/novo?mulher_id=' . $mulherId));

        $this->assertStringNotContainsString('acolhimento em aberto', $body);
    }

    public function testAcolhimentoDaFichaSemAtendimentoUnicoNaoPreSeleciona(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $this->criarAtendimentos($mulherId, ['em_andamento', 'em_andamento'], 'AC');

        $body = $this->corpo($this->get('acolhimentos/novo?mulher_id=' . $mulherId));

        $this->assertStringContainsString('name="atendimento_id" id="atendimento_id" value=""', $body);
    }

    /* ================================================================
     | Caso iniciado pela ficha
     * ================================================================ */

    public function testCasoPelaFichaUsaOAtendimentoUnicoEmAberto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $ids = $this->criarAtendimentos($mulherId, ['em_andamento'], 'CA');

        $body = $this->corpo($this->get('casos/novo?mulher_id=' . $mulherId));

        $this->assertStringContainsString('value="' . $ids[0] . '"', $body);
        $this->assertStringContainsString('Ver histórico', $body);
    }

    public function testCasoPelaFichaAvisaQuandoJaExisteCasoEmAberto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $ids = $this->criarAtendimentos($mulherId, ['em_andamento'], 'CA');

        $casoId = (new CasoModel())->insert([
            'protocolo'     => 'CA-2026-000001',
            'mulher_id'     => $mulherId,
            'prioridade'    => 'alta',
            'situacao'      => 'novo',
            'data_abertura' => '2026-02-01 09:00:00',
        ]);
        $this->assertIsInt($casoId);

        $body = $this->corpo($this->get('casos/novo?mulher_id=' . $mulherId));

        $this->assertStringContainsString('caso em aberto', $body);
        $this->assertStringContainsString('casos/' . $casoId, $body);
    }

    public function testCasoDaFichaNaoCopiaPrioridadeNemResumoDoAtendimento(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $ids = $this->criarAtendimentos($mulherId, ['em_andamento'], 'CA');

        (new AtendimentoModel())->update($ids[0], [
            'prioridade'     => 'alta',
            'descricao_caso' => 'Descricao que nao pode ser copiada',
        ]);

        $body = $this->corpo($this->get('casos/novo?mulher_id=' . $mulherId));

        $this->assertStringNotContainsString('Descricao que nao pode ser copiada', $body);
        $this->assertStringNotContainsString('<option value="alta" selected', $body);
    }

    public function testCasoPelaFichaRedirecionaQuandoNaoHaAtendimentoUnicoEmAberto(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();

        $this->get('casos/novo?mulher_id=' . $mulherId)->assertRedirect('/atendimentos/fila');
    }

    public function testCasoPeloAtendimentoContinuaFuncionando(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $ids = $this->criarAtendimentos($mulherId, ['em_andamento'], 'CA');

        $result = $this->get('casos/novo/atendimento/' . $ids[0]);

        $result->assertStatus(200);
        $this->assertStringContainsString('Abrir Caso', $this->corpo($result));
    }

    /* ================================================================
     | Atalhos da ficha
     * ================================================================ */

    public function testFichaOfereceOsAtalhosDeRegistroComAMulherPreselecionada(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();

        $body = $this->corpo($this->get('mulheres/detalhes/' . $mulherId));

        foreach ([
            'acolhimentos/novo',
            'casos/novo',
            'encaminhamentos/novo',
            'tarefas/nova',
            'agenda/novo',
            'medidas-protetivas/nova',
            'processos/nova',
            'documentos/novo',
            'manifestacoes/nova',
        ] as $rota) {
            $this->assertStringContainsString($rota . '?mulher_id=' . $mulherId, $body);
        }
    }

    /* ================================================================
     | Contexto nos demais formularios
     * ================================================================ */

    public function testContextoApareceNosDemaisFormulariosIniciadosPelaFicha(): void
    {
        $this->logarComoEquipe();
        $mulherId = $this->criarMulher();
        $this->criarAtendimentos($mulherId, ['em_andamento'], 'GE');

        foreach ([
            'encaminhamentos/novo',
            'tarefas/nova',
            'agenda/novo',
            'documentos/novo',
            'processos/nova',
            'manifestacoes/nova',
        ] as $rota) {
            $body = $this->corpo($this->get($rota . '?mulher_id=' . $mulherId));
            $this->assertStringContainsString('Ver histórico', $body, 'Faltou o contexto em ' . $rota);
            $this->assertStringContainsString('mulheres/historico/' . $mulherId, $body, 'Faltou o link do histórico em ' . $rota);
        }
    }

    public function testContextoNaoApareceQuandoOFuncaoEhIniciadaPeloModulo(): void
    {
        $this->logarComoEquipe();
        $this->criarMulher();

        $body = $this->corpo($this->get('encaminhamentos/novo'));

        $this->assertStringNotContainsString('Ver histórico', $body);
    }
}
