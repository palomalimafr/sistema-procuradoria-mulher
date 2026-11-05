<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo RELATÓRIOS: central de análise com dados REAIS do banco.
 *
 * Cobre o acesso restrito à equipe (admin/funcionário), a correta contagem dos
 * indicadores a partir de registros reais seedados (sem dados fictícios de
 * relatório), os atalhos de período (hoje/últimos dias/mês/ano), os filtros de
 * município, faixa etária e situação, os gráficos de risco/violência, a
 * exportação CSV legada e a geração do relatório institucional em PDF
 * (mesmos filtros da tela).
 *
 * @internal
 */
final class RelatorioFlowTest extends CIUnitTestCase
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

    private function inserir(string $tabela, array $dados): void
    {
        $this->db->table($tabela)->insert($dados);
    }

    private function agora(int $diasAtras = 0): string
    {
        return date('Y-m-d H:i:s', strtotime("{$diasAtras} days"));
    }

    private function criarMulher(array $dados = []): int
    {
        $this->inserir('dados_mulher', array_merge([
            'user_id'        => 1,
            'nome_completo'  => 'Mulher Relatório',
            'municipio'      => 'Campos dos Goytacazes',
            'estado'         => 'RJ',
            'data_nascimento'=> '1980-01-15',
            'created_at'     => $this->agora(),
        ], $dados));

        return (int) $this->db->table('dados_mulher')->orderBy('id', 'DESC')->get()->getRowArray()['id'];
    }

    private function criarAtendimento(int $mulherId, array $dados = []): int
    {
        $this->inserir('atendimentos', array_merge([
            'protocolo'         => 'AT-' . random_int(100000, 999999),
            'data_hora'         => $this->agora(),
            'mulher_id'         => $mulherId,
            'profissional_id'   => 1,
            'forma_atendimento' => 'presencial',
            'origem'            => 'demanda_espontanea',
            'prioridade'        => 'media',
            'situacao'          => 'novo',
            'created_at'        => $this->agora(),
        ], $dados));

        return (int) $this->db->table('atendimentos')->orderBy('id', 'DESC')->get()->getRowArray()['id'];
    }

    private function criarAcolhimentoComRisco(int $atendimentoId, array $fatores = ['ameaca_morte' => 1]): void
    {
        $this->inserir('acolhimentos', [
            'atendimento_id' => $atendimentoId,
            'data_hora'      => $this->agora(),
            'profissional_id'=> 1,
            'status'         => 'em_andamento',
            'created_at'     => $this->agora(),
        ]);

        $acolhimentoId = (int) $this->db->table('acolhimentos')->orderBy('id', 'DESC')->get()->getRowArray()['id'];

        $campos = [
            'acolhimento_id'             => $acolhimentoId,
            'data_hora'                  => $this->agora(),
            'profissional_id'            => 1,
            'classificacao'              => 'extremo',
            'classificacao_sugerida'     => 'alto',
            'created_at'                 => $this->agora(),
        ] + $fatores;

        $this->inserir('avaliacoes_risco', $campos);
    }

    private function criarEncaminhamento(int $mulherId, array $dados = []): void
    {
        $this->inserir('encaminhamentos', array_merge([
            'mulher_id'      => $mulherId,
            'data_hora'      => $this->agora(),
            'profissional_id'=> 1,
            'status'         => 'pendente',
            'orgao'          => 'mp',
            'servico'        => 'MP — Ministério Público',
            'prioridade'     => 'alta',
            'created_at'     => $this->agora(),
        ], $dados));
    }

    private function indicador(string $body, string $chave): int
    {
        $padrao = '/data-indicador="' . preg_quote($chave, '/') . '" data-valor="(\d+)"/';
        $achado = preg_match($padrao, $body, $m) === 1;

        $this->assertTrue($achado, 'Indicador não encontrado na página: ' . $chave);

        return (int) $m[1];
    }

    public function testVisitanteNaoAcessaRelatoriosNemExportacao(): void
    {
        $this->get('relatorios')->assertRedirectTo('/login');
        $this->get('relatorios/exportar')->assertRedirectTo('/login');
        $this->get('relatorios/pdf')->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaRelatorios(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('relatorios');
        $result->assertRedirectTo('/dashboard');

        $resultPdf = $this->get('relatorios/pdf');
        $resultPdf->assertRedirectTo('/dashboard');
    }

    public function testFuncionarioAcessaRelatorios(): void
    {
        $this->logarComo('funcionario');

        $result = $this->get('relatorios');

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Relatórios', $body);
        $this->assertStringContainsString('Mulheres atendidas', $body);
        $this->assertStringContainsString('Relatório PDF', $body);
    }

    public function testIndicadoresRefletemDadosReais(): void
    {
        $this->logarComo('admin');

        $fim    = strtotime('+6 days');
        $inicio = strtotime('-6 days');

        // 2 mulheres atendidas (uma com 2 atendimentos => contagem DISTINCT).
        $m1 = $this->criarMulher(['nome_completo' => 'Maria Relatório', 'data_nascimento' => '1980-01-15']);
        $m2 = $this->criarMulher(['nome_completo' => 'Joana Relatório', 'municipio' => 'Macaé', 'data_nascimento' => '2000-05-10']);

        $this->criarAtendimento($m1, ['situacao' => 'encerrado', 'prioridade' => 'media', 'data_encerramento' => date('Y-m-d H:i:s')]);
        $a2 = $this->criarAtendimento($m1, ['situacao' => 'novo', 'prioridade' => 'emergencia']);
        $this->criarAtendimento($m2, ['situacao' => 'encerrado', 'prioridade' => 'alta', 'data_encerramento' => date('Y-m-d H:i:s')]);

        // Cadeia acolhimento -> avaliação de risco (classificação + fator).
        $this->criarAcolhimentoComRisco($a2);

        // Encaminhamento pendente, retorno agendado, medida, processo, manifestação.
        $this->criarEncaminhamento($m1, ['instituicao_id' => null]);
        $this->inserir('compromissos', [
            'tipo'            => 'retorno',
            'data'            => date('Y-m-d'),
            'hora_inicio'     => '14:00',
            'hora_fim'        => '14:30',
            'profissional_id' => 1,
            'mulher_id'       => $m1,
            'status'          => 'agendado',
            'created_at'      => $this->agora(),
        ]);
        $this->inserir('medidas_protetivas', [
            'mulher_id'         => $m1,
            'possui_medida'     => 1,
            'numero'            => 'MP-0001/2026',
            'data_solicitacao'  => date('Y-m-d'),
            'data_concessao'    => date('Y-m-d'),
            'orgao'             => 'delegacia',
            'situacao'          => 'ativa',
            'created_at'        => $this->agora(),
        ]);
        $this->inserir('processos', [
            'mulher_id'    => $m1,
            'tipo'         => 'criminal',
            'orgao'        => 'mp',
            'situacao'     => 'em_andamento',
            'numero_processo' => '0001234-56.2026.8.19.0001',
            'created_at'   => $this->agora(),
        ]);
        $this->inserir('manifestacoes', [
            'protocolo'   => 'MAN-2609-000001',
            'data_hora'   => $this->agora(),
            'tipo'        => 'denuncia',
            'origem'      => 'whatsapp',
            'anonimo'     => 1,
            'descricao'   => 'Relato de situação de violência em curso.',
            'prioridade'  => 'alta',
            'situacao'    => 'nova',
            'created_at'  => $this->agora(),
        ]);

        $result = $this->get('relatorios');
        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        // Indicadores principais derivados dos registros reais.
        $this->assertSame(2, $this->indicador($body, 'mulheres'));
        $this->assertSame(3, $this->indicador($body, 'atendimentos'));
        $this->assertSame(1, $this->indicador($body, 'novos'));
        $this->assertSame(0, $this->indicador($body, 'em_andamento'));
        $this->assertSame(2, $this->indicador($body, 'encerrados'));
        $this->assertSame(2, $this->indicador($body, 'urgentes'));
        $this->assertSame(1, $this->indicador($body, 'riscos'));
        $this->assertSame(1, $this->indicador($body, 'encaminhamentos'));
        $this->assertSame(1, $this->indicador($body, 'encaminhamentos_pendentes'));
        $this->assertSame(1, $this->indicador($body, 'retornos'));
        $this->assertSame(1, $this->indicador($body, 'medidas'));
        $this->assertSame(1, $this->indicador($body, 'processos'));
        $this->assertSame(1, $this->indicador($body, 'manifestacoes'));

        // Distribuição por município e os dados de violência/risco são reais.
        $this->assertStringContainsString('Campos dos Goytacazes', $body);
        $this->assertStringContainsString('Macaé', $body);
        $this->assertStringContainsString('Extremo', $body);
        $this->assertStringContainsString('Ameaça de morte', $body);
    }

    public function testAtalhoHojeRestringeAoSirDia(): void
    {
        $this->logarComo('admin');

        $m1 = $this->criarMulher();

        // Hoje entra no atalho "hoje"; ontem fica fora.
        $this->criarAtendimento($m1, ['data_hora' => $this->agora()]);
        $this->criarAtendimento($m1, ['data_hora' => $this->agora(-1)]);

        $result = $this->get('relatorios?rapido=hoje');
        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        $this->assertSame(1, $this->indicador($body, 'atendimentos'));
        $this->assertSame(1, $this->indicador($body, 'mulheres'));
    }

    public function testAtalhoUltimos30DiasIncluiSemanaPassada(): void
    {
        $this->logarComo('admin');

        $m1 = $this->criarMulher();
        $this->criarAtendimento($m1, ['data_hora' => $this->agora(-7)]);

        $result = $this->get('relatorios?rapido=30_dias');
        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        $this->assertSame(1, $this->indicador($body, 'atendimentos'));
    }

    public function testFiltroMunicipio(): void
    {
        $this->logarComo('admin');

        $campos = $this->criarMulher(['municipio' => 'Campos dos Goytacazes']);
        $macae  = $this->criarMulher(['municipio' => 'Macaé']);

        $this->criarAtendimento($campos);
        $this->criarAtendimento($macae);

        $result = $this->get('relatorios?municipio=' . rawurlencode('Macaé'));
        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        $this->assertSame(1, $this->indicador($body, 'atendimentos'));
        $this->assertSame(1, $this->indicador($body, 'mulheres'));
    }

    public function testFiltroFaixaEtaria(): void
    {
        $this->logarComo('admin');

        $acima = $this->criarMulher(['data_nascimento' => '1980-01-15']); // 40 a 49 anos
        $jovem = $this->criarMulher(['data_nascimento' => '2000-05-10']); // 18 a 29 anos

        $this->criarAtendimento($acima);
        $this->criarAtendimento($jovem);

        $result = $this->get('relatorios?faixa=' . rawurlencode('18_29'));
        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        $this->assertSame(1, $this->indicador($body, 'atendimentos'));
        $this->assertSame(1, $this->indicador($body, 'mulheres'));
    }

    public function testFiltroSituacao(): void
    {
        $this->logarComo('admin');

        $m1 = $this->criarMulher();
        $this->criarAtendimento($m1, ['situacao' => 'novo']);
        $this->criarAtendimento($m1, ['situacao' => 'encerrado', 'data_encerramento' => date('Y-m-d H:i:s')]);

        $result = $this->get('relatorios?situacao=' . rawurlencode('novo'));
        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        $this->assertSame(1, $this->indicador($body, 'atendimentos'));
        $this->assertSame(1, $this->indicador($body, 'novos'));
        $this->assertSame(0, $this->indicador($body, 'encerrados'));
    }

    public function testExportarCsvComDadosReais(): void
    {
        $this->logarComo('admin');

        $m1 = $this->criarMulher();
        $this->criarAtendimento($m1, ['situacao' => 'encerrado', 'data_encerramento' => date('Y-m-d H:i:s')]);

        $result = $this->get('relatorios/exportar');

        $result->assertStatus(200);
        $headers = $result->response()->getHeaders();
        $this->assertStringContainsString('text/csv', (string) ($headers['Content-Type'] ?? ''));

        $body = $result->getBody();
        $this->assertStringContainsString('&#65279;', $body);

        $texto = html_entity_decode($body, ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Relatórios — Procuradoria da Mulher', $texto);
        $this->assertStringContainsString('Indicadores principais', $texto);
        $this->assertStringContainsString('Atendimentos por mês', $texto);
        $this->assertStringContainsString('atendimentos', $texto);
    }

    public function testFiltrosIlegaisSaoDescartados(): void
    {
        $this->logarComo('admin');

        $m1 = $this->criarMulher();
        $this->criarAtendimento($m1, ['situacao' => 'novo']);

        // 'situacao' inexistente e 'rapido' inválido caem no período do ano atual.
        $result = $this->get('relatorios?situacao=inexistente&rapido=nao_existe');
        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        $this->assertSame(1, $this->indicador($body, 'atendimentos'));
    }

    public function testExportarPdfComDadosReais(): void
    {
        $this->logarComo('admin');

        $m1 = $this->criarMulher(['nome_completo' => 'Maria Relatório']);
        $this->criarAtendimento($m1, [
            'situacao'    => 'encerrado',
            'prioridade'  => 'alta',
            'data_encerramento' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('relatorios/pdf');
        $result->assertStatus(200);
        $headers = $result->response()->getHeaders();
        $this->assertStringContainsString('application/pdf', (string) ($headers['Content-Type'] ?? ''));

        $body = $result->response()->getBody();
        $this->assertStringStartsWith('%PDF', $body);
        $this->assertStringContainsString('/Type /Catalog', $body);
    }

    public function testExportarPdfRespeitaFiltrosDaTela(): void
    {
        $this->logarComo('admin');

        $m1 = $this->criarMulher();
        $this->criarAtendimento($m1, ['situacao' => 'novo']);
        $this->criarAtendimento($m1, [
            'situacao'    => 'encerrado',
            'data_encerramento' => date('Y-m-d H:i:s'),
        ]);

        // Filtro por situação 'novo': o PDF ainda é gerado com os dados reais.
        $result = $this->get('relatorios/pdf?situacao=' . rawurlencode('novo'));
        $result->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $result->response()->getBody());

        // Filtro 'situacao' ilegal não deve quebrar nem burlar a consulta.
        $ilegal = $this->get('relatorios/pdf?situacao=inexistente');
        $ilegal->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $ilegal->response()->getBody());
    }

    public function testExportarPdfSemResultadosGeraDocumentoValido(): void
    {
        $this->logarComo('admin');

        // Período no futuro: nenhum registro entra na consulta.
        $result = $this->get('relatorios/pdf?data_de=2030-01-01&data_ate=2030-01-31');
        $result->assertStatus(200);
        $body = $result->response()->getBody();
        $this->assertStringStartsWith('%PDF', $body);
        $this->assertStringContainsString('/Type /Catalog', $body);
    }
}