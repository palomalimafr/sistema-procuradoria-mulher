<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do RELATÓRIO INDIVIDUAL da mulher em PDF (dossiê).
 *
 * Cobre a quarta ação na tela Mulheres > Pesquisa, a geração do PDF por mulher
 * (ficha cadastral + histórico consolidado por módulo), os vínculos indiretos
 * (acolhimento/risco via atendimento e encaminhamento pela cadeia), o isolamento
 * entre mulheres, mulher sem histórico, a proteção da rota e a preservação do
 * relatório geral e das ações já existentes.
 *
 * Os testes de PDF verificam o corpo real da resposta (assinatura %PDF,
 * catálogo) e o texto extraído dos streams comprimidos — mesmo padrão de corpo
 * real já usado no RelatorioFlowTest, indo além do status HTTP.
 *
 * @internal
 */
final class MulherPdfFlowTest extends CIUnitTestCase
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

    /**
     * Concatena o conteúdo de todos os streams FlateDecode do PDF. O Dompdf
     * grava o texto em claro (ASCII) dentro do stream comprimido, o que permite
     * verificar a presença real dos dados sem depender de biblioteca externa.
     */
    private function textoDoPdf(string $pdf): string
    {
        $texto  = '';
        $offset = 0;

        while (($inicio = strpos($pdf, 'stream', $offset)) !== false) {
            $fim = strpos($pdf, 'endstream', $inicio);
            if ($fim === false) {
                break;
            }

            $dados = ltrim(substr($pdf, $inicio + 6, $fim - $inicio - 6), "\r\n");
            $inflado = @gzuncompress($dados);
            if ($inflado !== false) {
                $texto .= $inflado . "\n";
            }

            $offset = $fim + 9;
        }

        // A view usa a fonte DejaVu embutida, que grava o texto em pares UTF-16
        // (byte nulo + caractere). Removê-los devolve o texto ASCII pesquisável.
        return str_replace("\x00", '', $texto);
    }

    private function pdf(string $caminho): string
    {
        $result = $this->get($caminho);
        $result->assertStatus(200);

        $body = $result->response()->getBody();
        $this->assertStringStartsWith('%PDF', $body, 'A resposta não é um PDF.');

        return $body;
    }

    private function inserirAtendimentoSimples(int $mulherId, string $protocolo): int
    {
        db_connect()->table('atendimentos')->insert([
            'protocolo'  => $protocolo,
            'data_hora'  => '2025-10-01 09:00:00',
            'mulher_id'  => $mulherId,
            'situacao'   => 'novo',
            'prioridade' => 'media',
        ]);

        return (int) db_connect()->table('atendimentos')->orderBy('id', 'DESC')->get()->getRowArray()['id'];
    }

    // ------------------------------------------------------------------
    // Tela Mulheres > Pesquisa
    // ------------------------------------------------------------------

    public function testPesquisaExibeQuartaAcaoRelatorioPdf(): void
    {
        $this->logarComo('admin');
        $mulher = (new App\Models\MulherModel())->insert([
            'nome_completo' => 'Mulher Acao PDF',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('mulheres/pesquisa');
        $result->assertStatus(200);
        $corpo = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');

        // A quarta ação existe e aponta para o relatório individual da mulher.
        $this->assertStringContainsString('mulheres/relatorio-pdf/' . (int) $mulher, $corpo);
        $this->assertStringContainsString('Relatório PDF', $corpo);

        // As três ações anteriores continuam presentes (preservação da tela).
        $this->assertStringContainsString('mulheres/detalhes/' . (int) $mulher, $corpo);
        $this->assertStringContainsString('mulheres/editar/' . (int) $mulher, $corpo);
        $this->assertStringContainsString('mulheres/historico/' . (int) $mulher, $corpo);
    }

    // ------------------------------------------------------------------
    // Geração do PDF e ficha cadastral
    // ------------------------------------------------------------------

    public function testGeraPdfIndividualComDadosDaFicha(): void
    {
        $this->logarComo('admin');
        $mulher = (new App\Models\MulherModel())->insert([
            'nome_completo'   => 'Maria Silva Dossie',
            'cpf'             => '11144477735',
            'municipio'       => 'Campos dos Goytacazes',
            'estado'          => 'RJ',
            'data_nascimento' => '1980-01-15',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('mulheres/relatorio-pdf/' . (int) $mulher);
        $result->assertStatus(200);

        $headers = $result->response()->getHeaders();
        $this->assertStringContainsString('application/pdf', (string) ($headers['Content-Type'] ?? ''));

        $body = $result->response()->getBody();
        $this->assertStringStartsWith('%PDF', $body);
        $this->assertStringContainsString('/Type /Catalog', $body);

        $texto = $this->textoDoPdf($body);
        $this->assertStringContainsString('Maria Silva Dossie', $texto);
        $this->assertStringContainsString('11144477735', $texto);
        $this->assertStringContainsString('Campos dos Goytacazes', $texto);
    }

    public function testIncluiRegistrosDeDiferentesModulos(): void
    {
        $this->logarComo('admin');
        $mulher = (int) (new App\Models\MulherModel())->insert([
            'nome_completo' => 'Mulher Multi Modulos',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $atendimento = $this->inserirAtendimentoSimples($mulher, 'AT-MOD-1');

        db_connect()->table('acolhimentos')->insert([
            'atendimento_id' => $atendimento,
            'data_hora'      => '2025-10-02 10:00:00',
            'status'         => 'em_andamento',
        ]);
        $acolhimento = (int) db_connect()->table('acolhimentos')->orderBy('id', 'DESC')->get()->getRowArray()['id'];

        db_connect()->table('avaliacoes_risco')->insert([
            'acolhimento_id' => $acolhimento,
            'data_hora'      => '2025-10-02 10:30:00',
            'classificacao'  => 'extremo',
        ]);

        db_connect()->table('encaminhamentos')->insert([
            'mulher_id' => $mulher,
            'data_hora' => '2025-10-03 08:00:00',
            'orgao'     => 'CREAS-MOD-9',
            'status'    => 'pendente',
        ]);

        db_connect()->table('medidas_protetivas')->insert([
            'mulher_id' => $mulher,
            'numero'    => 'MED-MOD-7',
            'situacao'  => 'concedida',
        ]);
        $medida = (int) db_connect()->table('medidas_protetivas')->orderBy('id', 'DESC')->get()->getRowArray()['id'];

        db_connect()->table('medida_descumprimentos')->insert([
            'medida_id'       => $medida,
            'data_ocorrencia' => '2025-10-04 07:00:00',
            'descricao'       => 'DESC-MOD-3',
            'created_at'      => '2025-10-04 08:00:00',
        ]);

        db_connect()->table('processos')->insert([
            'mulher_id'       => $mulher,
            'numero_processo' => 'PROC-MOD-5',
            'situacao'        => 'em_andamento',
            'data'            => '2025-10-05',
        ]);

        db_connect()->table('documentos')->insert([
            'mulher_id'        => $mulher,
            'arquivo_original' => 'DOC-MOD-2.pdf',
            'arquivo'          => 'documentos/DOC-MOD-2.pdf',
            'sigilo'           => 'publico',
            'created_at'       => '2025-10-06 09:00:00',
        ]);

        db_connect()->table('compromissos')->insert([
            'mulher_id'   => $mulher,
            'tipo'        => 'audiencia',
            'data'        => '2025-10-07',
            'hora_inicio' => '09:00:00',
            'observacoes' => 'COMPMOD8',
            'status'      => 'agendado',
        ]);

        db_connect()->table('tarefas')->insert([
            'titulo'   => 'TAREFA-MOD-6',
            'mulher_id'=> $mulher,
            'status'   => 'pendente',
        ]);

        db_connect()->table('manifestacoes')->insert([
            'protocolo' => 'MAN-MOD-4',
            'data_hora' => '2025-10-08 11:00:00',
            'descricao' => 'Manifestacao de teste',
            'mulher_id' => $mulher,
            'situacao'  => 'recebida',
        ]);

        db_connect()->table('casos')->insert([
            'protocolo'      => 'CASO-MOD-0',
            'mulher_id'      => $mulher,
            'data_abertura'  => '2025-10-09',
            'situacao'       => 'aberto',
        ]);

        db_connect()->table('pedidos_sos')->insert([
            'mulher_id'      => $mulher,
            'data_hora'      => '2025-11-20 10:00:00',
            'status'         => 'pendente',
            'visualizado_em' => '2025-11-20 10:00:00',
        ]);

        $texto = $this->textoDoPdf($this->pdf('mulheres/relatorio-pdf/' . $mulher));

        foreach ([
            'AT-MOD-1',
            'Extremo',
            'CREAS-MOD-9',
            'MED-MOD-7',
            'DESC-MOD-3',
            'PROC-MOD-5',
            'DOC-MOD-2.pdf',
            'COMPMOD8',
            'TAREFA-MOD-6',
            'MAN-MOD-4',
            'CASO-MOD-0',
        ] as $token) {
            $this->assertStringContainsString($token, $texto, 'PDF não contém o registro: ' . $token);
        }
    }

    public function testIncluiRegistrosIndiretosViaAtendimento(): void
    {
        $this->logarComo('admin');
        $mulher = (int) (new App\Models\MulherModel())->insert([
            'nome_completo' => 'Mulher Vinculo Indireto',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $atendimento = $this->inserirAtendimentoSimples($mulher, 'AT-IND-1');

        // Acolhimento e avaliação de risco não têm mulher_id: só chegam ao
        // relatório pela cadeia acolhimento → atendimento → mulher.
        db_connect()->table('acolhimentos')->insert([
            'atendimento_id' => $atendimento,
            'data_hora'      => '2025-10-02 10:00:00',
            'status'         => 'em_andamento',
        ]);
        $acolhimento = (int) db_connect()->table('acolhimentos')->orderBy('id', 'DESC')->get()->getRowArray()['id'];

        db_connect()->table('avaliacoes_risco')->insert([
            'acolhimento_id' => $acolhimento,
            'data_hora'      => '2025-10-02 10:30:00',
            'classificacao'  => 'extremo',
        ]);

        $texto = $this->textoDoPdf($this->pdf('mulheres/relatorio-pdf/' . $mulher));

        $this->assertStringContainsString('Extremo', $texto, 'Avaliação de risco indireta ausente.');
        $this->assertStringContainsString('AT-IND-1', $texto, 'Atendimento da cadeia ausente.');
    }

    public function testNaoIncluiRegistrosDeOutraMulher(): void
    {
        $this->logarComo('admin');

        $ana  = (int) (new App\Models\MulherModel())->insert([
            'nome_completo' => 'Ana Isolada Dossie',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $bia  = (int) (new App\Models\MulherModel())->insert([
            'nome_completo' => 'Beatriz Excluida Dossie',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->inserirAtendimentoSimples($ana, 'AT-SO-A-1');
        $this->inserirAtendimentoSimples($bia, 'AT-SO-B-9');

        $texto = $this->textoDoPdf($this->pdf('mulheres/relatorio-pdf/' . $ana));

        $this->assertStringContainsString('Ana Isolada Dossie', $texto);
        $this->assertStringContainsString('AT-SO-A-1', $texto);
        $this->assertStringNotContainsString('Beatriz Excluida Dossie', $texto);
        $this->assertStringNotContainsString('AT-SO-B-9', $texto);
    }

    public function testMulherSemHistoricoGeraPdfValidoComAviso(): void
    {
        $this->logarComo('admin');
        $mulher = (int) (new App\Models\MulherModel())->insert([
            'nome_completo' => 'Mulher Sem Historico',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $body  = $this->pdf('mulheres/relatorio-pdf/' . $mulher);
        $texto = $this->textoDoPdf($body);

        $this->assertStringContainsString('/Type /Catalog', $body);
        $this->assertStringContainsString('Nenhum registro vinculado foi encontrado nos', $texto);
    }

    // ------------------------------------------------------------------
    // Proteção da rota e casos de erro
    // ------------------------------------------------------------------

    public function testIdInexistenteRedirecionaParaPesquisa(): void
    {
        $this->logarComo('admin');

        $result = $this->get('mulheres/relatorio-pdf/999999');
        $result->assertRedirectTo('/mulheres/pesquisa');
    }

    public function testVisitanteNaoAcessaRelatorioIndividual(): void
    {
        $result = $this->get('mulheres/relatorio-pdf/1');
        $result->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaRelatorioIndividual(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('mulheres/relatorio-pdf/1');
        $result->assertRedirectTo('/dashboard');
    }

    // ------------------------------------------------------------------
    // Preservação do relatório geral
    // ------------------------------------------------------------------

    public function testRelatorioGeralContinuaGeraPdf(): void
    {
        $this->logarComo('admin');

        $body = $this->pdf('relatorios/pdf');
        $this->assertStringContainsString('/Type /Catalog', $body);
    }
}
