<?php

use App\Models\AtendimentoModel;
use App\Models\MulherModel;
use App\Models\PedidoSosModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes de MULHERES > Pesquisa e Histórico (busca, ficha, edição).
 *
 * @internal
 */
final class MulheresPesquisaHistoricoFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    private ?int $profissionalId = null;

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

    /**
     * No ambiente de teste a resposta chega com os acentos convertidos em
     * entidades HTML, entao o corpo precisa ser decodificado antes de comparar
     * textos acentuados. Mesma convencao ja usada nos demais fluxos.
     */
    private function corpo(TestResponse $resultado): string
    {
        return html_entity_decode($resultado->getBody(), ENT_QUOTES, 'UTF-8');
    }

    private function criarMulher(array $dados): array
    {
        $defaults = [
            'nome_completo'   => 'Mulher de Teste',
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

        $dados = array_merge($defaults, $dados);

        $dados = array_filter($dados, static fn ($v) => $v !== null);
        $id = (new MulherModel())->insert($dados);

        $this->assertIsInt($id);

        return ['id' => $id] + $dados;
    }

    private function criarAtendimento(int $mulherId, array $extra = []): int
    {
        $atendimentoModel = new AtendimentoModel();
        $atendimento = array_merge([
            'protocolo'        => $atendimentoModel->proximoProtocolo(),
            'data_hora'        => date('Y-m-d H:i:s'),
            'mulher_id'        => $mulherId,
            'forma_atendimento' => 'presencial',
            'origem'           => 'demanda_espontanea',
            'prioridade'       => 'media',
            'situacao'         => 'novo',
            'unidade_setor'    => 'Procuradoria',
        ], $extra);

        $id = $atendimentoModel->insert($atendimento);
        $this->assertIsInt($id);

        return $id;
    }

    /**
     * A timeline mostra o profissional/responsavel, entao a fixture precisa de
     * um usuario de verdade — senao o rotulo some por valor vazio.
     */
    private function criarProfissional(string $nome = 'Profissional da Linha'): int
    {
        $this->profissionalId ??= (int) db_connect()->table('users')->insert([
            'nome'   => $nome,
            'email'  => 'linha-' . uniqid('', true) . '@teste.com',
            'senha'  => password_hash('teste1234', PASSWORD_DEFAULT),
            'perfil' => 'funcionario',
            'ativo'  => 1,
        ]);

        return $this->profissionalId;
    }

    private function criarPedidoSos(int $mulherId): int
    {
        $id = (new PedidoSosModel())->insert([
            'mulher_id' => $mulherId,
            'data_hora' => date('Y-m-d H:i:s'),
            'status'    => 'pendente',
            'latitude'  => -21.75,
            'longitude' => -41.3333,
        ]);
        $this->assertIsInt($id);

        return $id;
    }

    public function testPaginaPesquisaCarregaParaEquipe(): void
    {
        $this->criarMulher(['nome_completo' => 'Pesquisa Inicial']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/pesquisa');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Pesquisa de Mulheres', $body);
        $this->assertStringContainsString('Pesquisa Inicial', $body);
        $this->assertStringContainsString('Novo cadastro', $body);
    }

    public function testPesquisaPorNomeFiltraResultados(): void
    {
        $this->criarMulher(['nome_completo' => 'Alice Souza', 'cpf' => '11144477735']);
        $this->criarMulher(['nome_completo' => 'Bruno Santos', 'cpf' => '22255588846']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/pesquisa?nome=Alice');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Alice Souza', $body);
        $this->assertStringNotContainsString('Bruno Santos', $body);
    }

    public function testBuscaRapidaPorCpfIgnoraMascara(): void
    {
        $this->criarMulher(['nome_completo' => 'Carla Dias', 'cpf' => '33366699959']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/pesquisa?q=333.666.999-59');

        $result->assertStatus(200);
        $this->assertStringContainsString('Carla Dias', $result->getBody());
    }

    public function testFiltroFaixaEtaria(): void
    {
        $this->criarMulher(['nome_completo' => 'Jovem Adulta', 'data_nascimento' => '2000-05-10']);
        $this->criarMulher(['nome_completo' => 'Senhora Madura', 'data_nascimento' => '1965-08-20']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/pesquisa?faixa_etaria=18_29');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Jovem Adulta', $body);
        $this->assertStringNotContainsString('Senhora Madura', $body);
    }

    public function testFiltroZona(): void
    {
        $this->criarMulher(['nome_completo' => 'Rural Zona', 'zona' => 'rural']);
        $this->criarMulher(['nome_completo' => 'Urbana Zona', 'zona' => 'urbana']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/pesquisa?zona=rural');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Rural Zona', $body);
        $this->assertStringNotContainsString('Urbana Zona', $body);
    }

    public function testStatusMostraUltimoAtendimentoDaMulher(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Status Ativa', 'cpf' => '10102030455']);
        $this->criarAtendimento($m['id'], ['situacao' => 'em_andamento', 'prioridade' => 'alta']);
        $this->criarMulher(['nome_completo' => 'Status Sem Atendimento', 'cpf' => '80809010011']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/pesquisa');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Em andamento', $body);
        $this->assertStringContainsString('Sem atendimento', $body);
        $this->assertStringContainsString('Status Ativa', $body);
    }

    public function testPesquisaMostraSeloComEContraConta(): void
    {
        $userId = (new UserModel())->insert([
            'nome'   => 'Conta Mulher',
            'email'  => 'conta.mulher@teste.com',
            'senha'  => 'senha123',
            'perfil' => 'mulher',
            'ativo'  => 1,
        ]);
        $this->assertIsInt($userId);

        $this->criarMulher(['nome_completo' => 'Com Conta Registrada', 'user_id' => $userId, 'cpf' => '50506070809']);
        $this->criarMulher(['nome_completo' => 'Sem Conta Registrada', 'cpf' => '10111213141']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/pesquisa');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Com conta', $body);
        $this->assertStringContainsString('Sem conta', $body);
    }

    public function testDetalhesExibeFichaDaMulher(): void
    {
        $m = $this->criarMulher([
            'nome_completo'   => 'Ficha Completa',
            'cpf'             => '22211133344',
            'data_nascimento' => '1990-03-15',
            'telefone'        => '(22) 2722-0000',
            'logradouro'      => 'Rua das Acácias',
            'numero'          => '99',
        ]);
        $this->logarComo('admin');

        $result = $this->get('mulheres/detalhes/' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Ficha Completa', $body);
        $this->assertStringContainsString('22211133344', $body);
        $this->assertStringContainsString('(22) 2722-0000', $body);
        $this->assertStringContainsString('Rua das Ac', $body);
        $this->assertStringContainsString('Editar', $body);
        $this->assertStringContainsString('Ver hist', $body);
        $this->assertStringContainsString('Novo atendimento', $body);
    }

    public function testEditarExibeFormularioComDadosPrefill(): void
    {
        $m = $this->criarMulher([
            'nome_completo' => 'Edicao Prefill',
            'email'         => 'edicao@prefill.com',
            'cpf'           => '30304040506',
        ]);
        $this->logarComo('admin');

        $result = $this->get('mulheres/editar/' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Editar Mulher', $body);
        $this->assertStringContainsString('value="Edicao Prefill"', $body);
        $this->assertStringContainsString('mulheres/atualizar/' . $m['id'], $body);
    }

    public function testAtualizarAlteraDadosDaMulher(): void
    {
        $m = $this->criarMulher([
            'nome_completo' => 'Antes de Editar',
            'cpf'           => '60607070889',
        ]);
        $this->logarComo('admin');

        $result = $this->post('mulheres/atualizar/' . $m['id'], [
            'nome_completo'      => 'Depois de Editar',
            'cpf'                => '606.070.708-89',
            'data_nascimento'    => '1988-01-25',
            'telefone'           => '(22) 99999-1111',
            'email'              => '',
            'zona'               => 'urbana',
        ]);

        $result->assertRedirectTo('/mulheres/detalhes/' . $m['id']);
        $result->assertSessionHas('sucesso');

        $atual = (new MulherModel())->find($m['id']);
        $this->assertSame('Depois de Editar', $atual['nome_completo']);
        $this->assertSame('(22) 99999-1111', $atual['telefone']);
    }

    public function testAtualizarPreservaVinculoComConta(): void
    {
        $userId = (new UserModel())->insert([
            'nome'   => 'Vinculo Mulher',
            'email'  => 'vinculo@teste.com',
            'senha'  => 'senha123',
            'perfil' => 'mulher',
            'ativo'  => 1,
        ]);
        $this->assertIsInt($userId);

        $m = $this->criarMulher([
            'nome_completo' => 'Vinculo Original',
            'user_id'       => $userId,
            'cpf'           => '40404040405',
        ]);
        $this->logarComo('admin');

        $this->post('mulheres/atualizar/' . $m['id'], [
            'nome_completo' => 'Vinculo Atualizado',
            'cpf'           => '40404040405',
            'email'         => '',
            'data_nascimento' => '',
        ]);

        $atual = (new MulherModel())->find($m['id']);
        $this->assertSame($userId, (int) $atual['user_id'], 'A edição nunca deve desvincular a conta da mulher.');
        $this->assertSame('Vinculo Atualizado', $atual['nome_completo']);
    }

    public function testHistoricoListaAtendimentosEPedidosSos(): void
    {
        $m = $this->criarMulher([
            'nome_completo' => 'Historico Completo',
            'cpf'           => '90908080706',
        ]);
        $atendimentoId = $this->criarAtendimento($m['id'], [
            'situacao'       => 'encerrado',
            'prioridade'     => 'baixa',
            'data_hora'      => '2026-01-10 09:00:00',
            'motivo_encerramento' => 'atendimento_concluido',
            'data_encerramento'   => '2026-01-10 10:00:00',
        ]);
        $sosId = $this->criarPedidoSos($m['id']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/historico/' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Historico Completo', $body);
        $this->assertStringContainsString((new AtendimentoModel())->find($atendimentoId)['protocolo'], $body);
        $this->assertStringContainsString('10/01/2026', $body);
        $this->assertStringContainsString('Pedidos SOS (1)', $body);
        $this->assertStringContainsString('-21.75', $body);
    }

    public function testHistoricoVazioDaLinhaFora(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Sem Registros', 'cpf' => '11109080706']);
        $this->logarComo('admin');

        $result = $this->get('mulheres/historico/' . $m['id']);

        $result->assertStatus(200);
        $body = $this->corpo($result);
        // A timeline é única: sem registros, há uma mensagem só, e os
        // contadores de módulo continuam zerados.
        $this->assertStringContainsString('Nenhum registro no histórico desta mulher', $body);
        $this->assertStringContainsString('Atendimentos (0)', $body);
        $this->assertStringContainsString('Pedidos SOS (0)', $body);
        $this->assertStringNotContainsString('data-modulo=', $body);
    }

    public function testDetalhesDeMulherInexistente(): void
    {
        $this->logarComo('admin');

        $result = $this->get('mulheres/detalhes/999999');

        $result->assertRedirectTo('/mulheres/pesquisa');
        $result->assertSessionHas('erro');
    }

    public function testMulherNaoAcessaPesquisaNemHistorico(): void
    {
        $this->logarComo('mulher');

        $pesquisa = $this->get('mulheres/pesquisa');
        $pesquisa->assertRedirectTo('/dashboard');

        $historico = $this->get('mulheres/historico/1');
        $historico->assertRedirectTo('/dashboard');
    }

    public function testVisitanteNaoAcessaPesquisa(): void
    {
        $result = $this->get('mulheres/pesquisa');

        $result->assertRedirectTo('/login');
    }

    // -----------------------------------------------------------------
    // Histórico: linha do tempo unificada
    // -----------------------------------------------------------------

    /**
     * Cria uma mulher e enche a base com um registro de cada módulo, em datas
     * escolhidas para dar à ordenação um resultado previsível.
     *
     * @return array{0: array{id: int}, 1: array<string, int>}
     */
    private function mulherComTrajetoria(): array
    {
        $m = $this->criarMulher([
            'nome_completo' => 'Trajetoria Completa',
            'cpf'           => '55566677708',
        ]);
        $id = (int) $m['id'];

        $db = db_connect();
        $ids = [];

        // --- módulo com data própria e vínculo direto ---
        $ids['atendimento'] = $this->criarAtendimento($id, [
            'data_hora'        => '2026-03-10 08:00:00',
            'situacao'         => 'encerrado',
            'prioridade'       => 'alta',
            'profissional_id'  => $this->criarProfissional('Tecnica do Atendimento'),
        ]);
        $ids['sos'] = $this->criarPedidoSos($id);
        $db->table('pedidos_sos')->update([
            'data_hora'     => '2026-05-20 22:15:00',
            'visualizado_em' => '2026-05-20 22:40:00',
        ], ['id' => $ids['sos']]);

        $ids['caso'] = (int) $db->table('casos')->insert([
            'protocolo'     => 'CAS-TESTE-0001',
            'mulher_id'     => $id,
            'prioridade'    => 'alta',
            'situacao'      => 'em_acolhimento',
            'data_abertura' => '2026-01-05 10:00:00',
        ]);

        $ids['encaminhamento'] = (int) $db->table('encaminhamentos')->insert([
            'mulher_id'      => $id,
            'orgao'          => 'creas',
            'servico'        => 'psicossocial',
            'status'         => 'pendente',
            'prioridade'     => 'alta',
            'prazo'          => '2026-04-01',
            'data_hora'      => '2026-02-11 09:30:00',
            'profissional_id' => $this->criarProfissional('Assistente do Encaminhamento'),
        ]);

        $ids['medida'] = (int) $db->table('medidas_protetivas')->insert([
            'mulher_id'        => $id,
            'numero'           => 'MP-77',
            'orgao'            => 'poder_judiciario',
            'situacao'         => 'concedida',
            'data_solicitacao' => '2026-03-01',
            'data_concessao'   => '2026-03-05',
        ]);

        $ids['processo'] = (int) $db->table('processos')->insert([
            'mulher_id'       => $id,
            'numero_processo' => 'PROC-2026-9',
            'tipo'            => 'outros',
            'orgao'           => 'poder_judiciario',
            'situacao'        => 'em_andamento',
            'data'            => '2026-04-02',
        ]);

        $ids['documento'] = (int) $db->table('documentos')->insert([
            'mulher_id'        => $id,
            'tipo'             => 'relatorios',
            'sigilo'           => 'restrito',
            'arquivo'          => 'a.pdf',
            'arquivo_original' => 'relatorio.pdf',
            // Sem data própria: a timeline usa a data de cadastro.
            'created_at'       => '2026-01-20 10:00:00',
        ]);

        $ids['compromisso'] = (int) $db->table('compromissos')->insert([
            'mulher_id' => $id,
            'tipo'      => 'retorno',
            'data'      => '2026-06-12',
            'hora_inicio' => '14:00:00',
            'hora_fim'  => '15:00:00',
            'status'    => 'agendado',
        ]);

        $ids['tarefa'] = (int) $db->table('tarefas')->insert([
            'mulher_id'      => $id,
            'titulo'         => 'Cobrar retorno do CREAS',
            'responsavel_id' => $this->criarProfissional('Responsavel pela Tarefa'),
            'prioridade'     => 'media',
            'status'         => 'pendente',
            // Prazo no futuro: NÃO pode virar a data do evento.
            'prazo'          => '2027-01-01',
            // Sem data própria: a timeline usa a data de cadastro.
            'created_at'     => '2026-05-02 09:00:00',
        ]);

        $ids['manifestacao'] = (int) $db->table('manifestacoes')->insert([
            'protocolo'   => 'MAN-TESTE-0001',
            'mulher_id'   => $id,
            'data_hora'   => '2026-02-02 11:00:00',
            'tipo'        => 'denuncia',
            'origem'      => 'presencial',
            'situacao'    => 'nova',
            'prioridade'  => 'media',
            'descricao'   => 'Denuncia registrada pela propria mulher',
        ]);

        // --- módulo sem mulher_id: chega pela cadeia ---
        $ids['acolhimento'] = (int) $db->table('acolhimentos')->insert([
            'atendimento_id'     => $ids['atendimento'],
            'data_hora'          => '2026-03-10 09:00:00',
            'status'             => 'concluido',
            'risco_identificado' => 'alto',
            'motivo_procura'     => 'Pedido de protecao',
            'data_conclusao'     => '2026-03-11 12:00:00',
        ]);

        $ids['avaliacao'] = (int) $db->table('avaliacoes_risco')->insert([
            'acolhimento_id' => $ids['acolhimento'],
            'data_hora'      => '2026-03-10 10:00:00',
            'ameaca_morte'   => 1,
            'agressao_fisica' => 1,
            'classificacao'  => 'alto',
        ]);

        $ids['descumprimento'] = (int) $db->table('medida_descumprimentos')->insert([
            'medida_id'       => $ids['medida'],
            'data_ocorrencia' => '2026-03-20',
            'descricao'       => 'Descumpriu a ordem de afastamento',
            'usuario_id'      => $this->criarProfissional('Registrou o Descumprimento'),
        ]);

        return [$m, $ids];
    }

    public function testTimelineReuneTodosOsModulosVinculados(): void
    {
        [$m, $ids] = $this->mulherComTrajetoria();

        $r = (new \App\Models\HistoricoMulherModel())->timeline((int) $m['id']);

        $modulos = array_unique(array_column($r['eventos'], 'modulo'));

        // Todos os 13 módulos precisam aparecer, inclusive os que só chegam
        // pela cadeia (acolhimento, avaliação, descumprimento).
        foreach (array_keys(\App\Models\HistoricoMulherModel::MODULOS) as $esperado) {
            $this->assertContains($esperado, $modulos, "O módulo {$esperado} não apareceu na linha do tempo.");
        }

        $this->assertSame(count($ids), count($r['eventos']), 'Cada registro deve virar exatamente um evento.');
    }

    public function testTimelineOrdenaDoMaisRecenteParaOMaisAntigo(): void
    {
        [$m] = $this->mulherComTrajetoria();

        $r = (new \App\Models\HistoricoMulherModel())->timeline((int) $m['id']);

        // A lista inteira tem de estar em ordem decrescente.
        $datas = array_column($r['eventos'], 'data');
        $ordenada = $datas;
        rsort($ordenada);
        $this->assertSame($ordenada, $datas, 'A linha do tempo precisa estar em ordem decrescente.');

        // Nenhum evento pode ficar sem data: sem isso ele sumiria da lista.
        $this->assertNotContains('', $datas, 'Todo evento precisa de uma data para ser ordenado.');

        $posicao = [];

        foreach ($r['eventos'] as $i => $e) {
            $posicao[$e['modulo']] = $i;
        }

        // Compromisso (12/06) é posterior ao SOS (20/05).
        $this->assertLessThan($posicao['sos'], $posicao['compromisso']);

        // O caso, de 05/01, é o registro mais antigo e fecha a lista.
        $this->assertSame('caso', $r['eventos'][count($r['eventos']) - 1]['modulo']);
        $this->assertStringStartsWith('2026-01-05', $r['eventos'][count($r['eventos']) - 1]['data']);
    }

    public function testTimelineUsaDataDeCadastroQuandoModuloNaoTemData(): void
    {
        [$m] = $this->mulherComTrajetoria();
        $db  = db_connect();

        // Processo, tarefa e documento não têm data de evento própria no
        // formato de entrada; todos precisam cair em created_at.
        $semData = $db->table('processos')->where('mulher_id', (int) $m['id'])->get()->getRowArray();
        $this->assertNotNull($semData);

        $r = (new \App\Models\HistoricoMulherModel())->timeline((int) $m['id']);
        $porModulo = [];

        foreach ($r['eventos'] as $e) {
            $porModulo[$e['modulo']] = $e;
        }

        // Tarefa: `prazo` é vencimento, não evento. A data tem de ser a de
        // cadastro, e o prazo continua visível como informação.
        $this->assertTrue($porModulo['tarefa']['dataCadastro']);
        $this->assertSame('2026-05-02 09:00:00', $porModulo['tarefa']['data']);
        $this->assertNotSame('2027-01-01', $porModulo['tarefa']['data'], 'O prazo não pode virar a data do evento.');

        // Documento também entra pela data de cadastro.
        $this->assertTrue($porModulo['documento']['dataCadastro']);
        $this->assertSame('2026-01-20 10:00:00', $porModulo['documento']['data']);

        // Processo tem data própria (2026-04-02) e NÃO deve usar created_at.
        $this->assertFalse($porModulo['processo']['dataCadastro']);
        $this->assertStringStartsWith('2026-04-02', $porModulo['processo']['data']);
    }

    public function testTimelineNaoRepeteDocumentoComVariosVinculos(): void
    {
        [$m, $ids] = $this->mulherComTrajetoria();
        $db = db_connect();

        // O mesmo documento apontando para a mulher E para um processo: é o
        // caso que geraria duplicata se o histórico andasse por dois caminhos.
        $db->table('documentos')->update(
            ['processo_id' => $ids['processo'], 'medida_protetiva_id' => $ids['medida']],
            ['id' => $ids['documento']]
        );

        $r = (new \App\Models\HistoricoMulherModel())->timeline((int) $m['id']);

        $documentos = array_values(array_filter(
            $r['eventos'],
            static fn (array $e): bool => $e['modulo'] === 'documento'
        ));

        $this->assertCount(1, $documentos, 'Um documento não pode aparecer duas vezes na timeline.');
    }

    public function testTimelineNaoRepeteAcolhimentoAcessadoPelosDoisCaminhos(): void
    {
        [$m, $ids] = $this->mulherComTrajetoria();

        // O acolhimento está ligado a um atendimento da mulher. Como o
        // atendimento também vira evento, os dois são eventos legítimos e
        // distintos — mas o acolhimento só pode aparecer uma vez.
        $r = (new \App\Models\HistoricoMulherModel())->timeline((int) $m['id']);

        $acolhimentos = array_values(array_filter(
            $r['eventos'],
            static fn (array $e): bool => $e['modulo'] === 'acolhimento'
        ));

        $this->assertCount(1, $acolhimentos);
        $this->assertSame((int) $ids['acolhimento'], $acolhimentos[0]['id']);
    }

    public function testFiltroDeModuloRestringeTimelineMasMantemTotais(): void
    {
        [$m] = $this->mulherComTrajetoria();
        $id  = (int) $m['id'];

        $completo = (new \App\Models\HistoricoMulherModel())->timeline($id);
        $filtrado = (new \App\Models\HistoricoMulherModel())->timeline($id, 'acolhimento');

        $this->assertGreaterThan(1, $completo['total']);
        $this->assertCount(1, $filtrado['eventos']);
        $this->assertSame('acolhimento', $filtrado['eventos'][0]['modulo']);

        // O contador dos outros módulos continua disponível no filtro.
        $this->assertSame(1, $filtrado['contagem']['atendimento']);
        $this->assertSame(1, $filtrado['contagem']['sos']);
        $this->assertSame($filtrado['total'], $completo['total']);
    }

    public function testFiltroDeModuloInvalidoVoltaParaTodos(): void
    {
        [$m] = $this->mulherComTrajetoria();
        $id  = (int) $m['id'];

        $r = (new \App\Models\HistoricoMulherModel())->timeline($id, 'modulo-que-nao-existe');

        $this->assertSame('', $r['modulo']);
        $this->assertCount($r['total'], $r['eventos']);
    }

    public function testPaginaDoHistoricoMostraFiltroComContadores(): void
    {
        [$m] = $this->mulherComTrajetoria();
        $this->logarComo('admin');

        $body = $this->corpo($this->get('mulheres/historico/' . $m['id']));

        $this->assertStringContainsString('Acolhimentos (1)', $body);
        $this->assertStringContainsString('Avaliações de risco (1)', $body);
        $this->assertStringContainsString('Descumprimentos (1)', $body);
        $this->assertStringContainsString('Processos (1)', $body);
        $this->assertStringContainsString('data-modulo="acolhimento"', $body);
        $this->assertStringContainsString('data-modulo="sos"', $body);
    }

    public function testPaginaDoHistoricoComFiltroMostraSoOModuloEscolhido(): void
    {
        [$m] = $this->mulherComTrajetoria();
        $this->logarComo('admin');

        $body = $this->corpo($this->get('mulheres/historico/' . $m['id'] . '?modulo=documento'));

        $this->assertStringContainsString('data-modulo="documento"', $body);
        $this->assertStringNotContainsString('data-modulo="acolhimento"', $body);
        $this->assertStringNotContainsString('data-modulo="sos"', $body);
    }

    public function testTimelinePreservaDadosQueOHistoricoAntigoMostrava(): void
    {
        [$m, $ids] = $this->mulherComTrajetoria();
        $this->logarComo('admin');

        $body = $this->corpo($this->get('mulheres/historico/' . $m['id']));

        // Caso: protocolo e situação.
        $this->assertStringContainsString('CAS-TESTE-0001', $body);
        $this->assertStringContainsString('Em acolhimento', $body);

        // Atendimento: protocolo, data/hora, prioridade, situação, origem e
        // profissional — o histórico antigo mostrava os seis.
        $protocolo = (new AtendimentoModel())->find($ids['atendimento'])['protocolo'];
        $this->assertStringContainsString($protocolo, $body);
        $this->assertStringContainsString('10/03/2026', $body);
        $this->assertStringContainsString('Alta', $body);
        $this->assertStringContainsString('Origem:', $body);
        $this->assertStringContainsString('Profissional:', $body);

        // SOS: localização, situação e data de visualização.
        $this->assertStringContainsString('-21.75', $body);
        $this->assertStringContainsString('Visualizado em:', $body);
    }

    public function testHistoricoDeUmaMulherNaoMostraRegistroDeOutra(): void
    {
        [$minha] = $this->mulherComTrajetoria();

        $outra = $this->criarMulher([
            'nome_completo' => 'Outra Mulher Qualquer',
            'cpf'           => '99988877766',
        ]);
        $this->criarAtendimento((int) $outra['id'], [
            'protocolo' => 'ATD-DA-OUTRA',
            'data_hora' => '2026-09-09 09:00:00',
        ]);

        $this->logarComo('admin');

        $body = $this->corpo($this->get('mulheres/historico/' . $minha['id']));

        $this->assertStringNotContainsString('ATD-DA-OUTRA', $body);
        $this->assertStringNotContainsString('Outra Mulher Qualquer', $body);

        $r = (new \App\Models\HistoricoMulherModel())->timeline((int) $minha['id']);

        foreach ($r['eventos'] as $e) {
            $this->assertStringNotContainsString('ATD-DA-OUTRA', (string) $e['titulo']);
        }
    }
}
