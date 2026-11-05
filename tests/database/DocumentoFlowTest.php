<?php

use App\Models\AtendimentoModel;
use App\Models\DocumentoHistoricoModel;
use App\Models\DocumentoModel;
use App\Models\MulherModel;
use App\Models\ProcessoModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\TestDataSeeder;

/**
 * Testes do módulo PROCESSOS > Documentos: upload persistido fora do docroot
 * com nome físico aleatório, mulher obrigatória, vínculos opcionais da MESMA
 * mulher, entrega controlada (visualização/download) com registro no histórico,
 * edição no mesmo registro (com substituição opcional de arquivo) e exclusão
 * LÓGICA que preserva o arquivo físico.
 *
 * Observação: em CLI não há POST multipart real; o teste popula $_FILES
 * manualmente e usa service('superglobals')->setFilesArray() antes de cada
 * requisição. O arquivo de teste tem conteúdo real (header PDF) para o
 * detector de MIME (finfo) identificar application/pdf.
 *
 * @internal
 */
final class DocumentoFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';
    protected $seed      = TestDataSeeder::class;

    /** @var array<int, string> Caminhos temporários de arquivos de teste. */
    private array $tmpArquivos = [];

    /** @var array<int, string> Caminhos físicos criados no upload dir. */
    private array $arquivosCriados = [];

    protected function tearDown(): void
    {
        foreach ($this->arquivosCriados as $full) {
            if (is_file($full)) {
                @unlink($full);
            }
        }
        foreach ($this->tmpArquivos as $tmp) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
        $_FILES = [];
        service('superglobals')->setFilesArray([]);

        parent::tearDown();
    }

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
            'nome_completo'   => 'Mulher Documento',
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
        $id = $model->insert([
            'protocolo'         => $model->proximoProtocolo(),
            'data_hora'         => date('Y-m-d H:i:s'),
            'mulher_id'         => $mulherId,
            'profissional_id'   => 1,
            'forma_atendimento' => 'presencial',
            'origem'            => 'demanda_espontanea',
            'prioridade'        => 'media',
            'situacao'          => 'novo',
            'unidade_setor'     => 'Procuradoria',
        ]);
        $this->assertIsInt($id);

        return $id;
    }

    private function criarProcesso(int $mulherId, string $numero = '0001234-56.2026.8.19.0014'): array
    {
        $model = new ProcessoModel();
        $id = $model->insert([
            'mulher_id'       => $mulherId,
            'numero_processo' => $numero,
            'tipo'            => 'outros',
            'orgao'           => 'poder_judiciario',
            'vara'            => '1ª Vara de Família',
            'comarca'         => 'Campos',
            'data'            => '2026-06-10',
            'situacao'        => 'em_andamento',
            'responsavel_id'  => 1,
        ]);
        $this->assertIsInt($id);

        return $model->find($id);
    }

    /**
     * Cria o arquivo físico de teste, popula $_FILES e o superglobal antes do
     * POST. Retorna o caminho temporário (para limpeza).
     */
    private function anexarArquivo(string $nome = 'laudo.pdf'): string
    {
        $conteudo = "%PDF-1.4\n"
            . "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\n"
            . "trailer<</Root 1 0 R>>\n"
            . "%%EOF\n";

        $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
        $tmp = sys_get_temp_dir() . '/doc_teste_' . bin2hex(random_bytes(6)) . '.' . $ext;
        file_put_contents($tmp, $conteudo);

        $_FILES['arquivo'] = [
            'name'      => $nome,
            'type'      => 'application/pdf',
            'tmp_name'  => $tmp,
            'error'     => 0,
            'size'      => strlen($conteudo),
            'full_path' => $tmp,
        ];
        service('superglobals')->setFilesArray($_FILES);

        $this->tmpArquivos[] = $tmp;

        return $tmp;
    }

    /**
     * Insere um documento direto no banco (com arquivo físico) para os
     * testes que não exercitam o fluxo de POST/upload.
     */
    private function criarDocumentoSeed(int $mulherId, array $extra = []): array
    {
        $subPasta = date('Y/m');
        $dir = WRITEPATH . 'uploads/documentos/' . $subPasta;
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $nomeArquivo = bin2hex(random_bytes(8)) . '.pdf';
        $full = $dir . '/' . $nomeArquivo;
        file_put_contents($full, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n");

        $id = (new DocumentoModel())->insert(array_merge([
            'mulher_id'        => $mulherId,
            'tipo'             => 'outros',
            'sigilo'           => 'restrito',
            'arquivo_original' => 'seed.pdf',
            'arquivo'          => 'documentos/' . $subPasta . '/' . $nomeArquivo,
            'tamanho_bytes'    => 52,
            'tipo_mime'        => 'application/pdf',
            'usuario_id'       => 1,
        ], $extra));
        $this->assertIsInt($id);

        $this->arquivosCriados[] = $full;

        return (new DocumentoModel())->find($id);
    }

    public function testVisitanteNaoAcessaDocumentos(): void
    {
        $lista = $this->get('documentos');
        $lista->assertRedirectTo('/login');

        $novo = $this->get('documentos/novo');
        $novo->assertRedirectTo('/login');
    }

    public function testPerfilMulherNaoAcessaDocumentos(): void
    {
        $this->logarComo('mulher');

        $result = $this->get('documentos');
        $result->assertRedirectTo('/dashboard');
    }

    public function testFuncionarioAcessaDocumentos(): void
    {
        $this->logarComo('funcionario');

        $result = $this->get('documentos');
        $result->assertStatus(200);
        $this->assertStringContainsString('Enviar documento', html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8'));
    }

    public function testUploadSemMulherEhRejeitado(): void
    {
        $this->logarComo('admin');
        $this->anexarArquivo();

        $result = $this->post('documentos', [
            'tipo'  => 'outros',
            'sigilo' => 'restrito',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new DocumentoModel())->countAllResults());
    }

    public function testUploadSemArquivoEhRejeitado(): void
    {
        $m = $this->criarMulher();
        $this->logarComo('admin');

        $result = $this->post('documentos', [
            'mulher_id' => $m['id'],
            'tipo'      => 'outros',
            'sigilo'    => 'restrito',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new DocumentoModel())->countAllResults());
    }

    public function testCriarDocumentoRegistraDadosArquivoEHistorico(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Doc Completo']);
        $this->logarComo('admin');
        $this->anexarArquivo('laudo_detalhado.pdf');

        $result = $this->post('documentos', [
            'mulher_id'   => $m['id'],
            'tipo'        => 'relatorios',
            'sigilo'      => 'confidencial',
            'observacoes' => 'Laudo psicológico da vítima.',
        ]);

        $result->assertStatus(302);
        $this->assertStringContainsString('/documentos/', $result->getRedirectUrl());

        $documento = (new DocumentoModel())->orderBy('id', 'DESC')->first();
        $this->assertNotNull($documento);
        $this->assertSame((int) $m['id'], (int) $documento['mulher_id']);
        $this->assertSame('relatorios', $documento['tipo']);
        $this->assertSame('confidencial', $documento['sigilo']);
        $this->assertSame('laudo_detalhado.pdf', $documento['arquivo_original']);
        $this->assertSame('application/pdf', $documento['tipo_mime']);
        $this->assertSame(1, (int) $documento['usuario_id']);
        $this->assertSame('Laudo psicológico da vítima.', $documento['observacoes']);
        $this->assertNull($documento['atendimento_id']);
        $this->assertNull($documento['processo_id']);

        $this->assertMatchesRegularExpression(
            '#^documentos/\d{4}/\d{2}/[0-9a-f]{32}\.pdf$#',
            (string) $documento['arquivo']
        );
        $this->assertFileExists(WRITEPATH . 'uploads/' . $documento['arquivo']);
        $this->arquivosCriados[] = WRITEPATH . 'uploads/' . $documento['arquivo'];

        $historico = (new DocumentoHistoricoModel())
            ->where('documento_id', (int) $documento['id'])
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($historico);
        $this->assertSame('upload', $historico['acao']);
        $this->assertSame('confidencial', $historico['status_novo']);
        $this->assertSame(1, (int) $historico['usuario_id']);
    }

    public function testCriarDocumentoComVinculosDaMesmaMulher(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Doc Vinc']);
        $atendimentoId = $this->criarAtendimento($m['id']);
        $processo = $this->criarProcesso($m['id']);
        $this->logarComo('admin');
        $this->anexarArquivo();

        $result = $this->post('documentos', [
            'mulher_id'       => $m['id'],
            'atendimento_id'  => $atendimentoId,
            'processo_id'     => (int) $processo['id'],
            'tipo'            => 'outros',
            'sigilo'          => 'restrito',
        ]);

        $result->assertStatus(302);

        $documento = (new DocumentoModel())->orderBy('id', 'DESC')->first();
        $this->assertSame($atendimentoId, (int) $documento['atendimento_id']);
        $this->assertSame((int) $processo['id'], (int) $documento['processo_id']);
        $this->arquivosCriados[] = WRITEPATH . 'uploads/' . $documento['arquivo'];
    }

    public function testRejeitaAtendimentoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Doc Vinc A']);
        $m2 = $this->criarMulher(['nome_completo' => 'Doc Vinc B']);
        $atendimentoId = $this->criarAtendimento($m1['id']);
        $this->logarComo('admin');
        $this->anexarArquivo();

        $result = $this->post('documentos', [
            'mulher_id'      => $m2['id'],
            'atendimento_id' => $atendimentoId,
            'tipo'           => 'outros',
            'sigilo'         => 'restrito',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new DocumentoModel())->countAllResults());
    }

    public function testRejeitaProcessoDeOutraMulher(): void
    {
        $m1 = $this->criarMulher(['nome_completo' => 'Doc Proc A']);
        $m2 = $this->criarMulher(['nome_completo' => 'Doc Proc B']);
        $processo = $this->criarProcesso($m1['id']);
        $this->logarComo('admin');
        $this->anexarArquivo();

        $result = $this->post('documentos', [
            'mulher_id'   => $m2['id'],
            'processo_id' => (int) $processo['id'],
            'tipo'        => 'outros',
            'sigilo'      => 'restrito',
        ]);

        $result->assertStatus(302);
        $result->assertSessionHas('erros');
        $this->assertSame(0, (new DocumentoModel())->countAllResults());
    }

    public function testListagemExibeDocumento(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Mulher Listagem Doc']);
        $this->criarDocumentoSeed($m['id'], ['arquivo_original' => 'declaracao.pdf']);
        $this->logarComo('admin');

        $result = $this->get('documentos');

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('declaracao.pdf', $body);
        $this->assertStringContainsString('Mulher Listagem Doc', $body);
        $this->assertStringContainsString('Restrito', $body);
    }

    public function testFiltroPorTipo(): void
    {
        $m = $this->criarMulher();
        $this->criarDocumentoSeed($m['id'], [
            'arquivo_original' => 'laudo_tipo.pdf',
            'tipo'             => 'relatorios',
        ]);
        $this->criarDocumentoSeed($m['id'], [
            'arquivo_original' => 'declaracao_tipo.pdf',
            'tipo'             => 'declaracoes',
        ]);
        $this->logarComo('admin');

        $result = $this->get('documentos?tipo=declaracoes');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('declaracao_tipo.pdf', $body);
        $this->assertStringNotContainsString('laudo_tipo.pdf', $body);
    }

    public function testFiltroPorSigilo(): void
    {
        $m = $this->criarMulher();
        $this->criarDocumentoSeed($m['id'], [
            'arquivo_original' => 'doc_sigiloso.pdf',
            'sigilo'           => 'sigiloso',
        ]);
        $this->criarDocumentoSeed($m['id'], [
            'arquivo_original' => 'doc_restrito.pdf',
            'sigilo'           => 'restrito',
        ]);
        $this->logarComo('admin');

        $result = $this->get('documentos?sigilo=sigiloso');

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('doc_sigiloso.pdf', $body);
        $this->assertStringNotContainsString('doc_restrito.pdf', $body);
    }

    public function testDetalhesExibeDadosArquivoEHistorico(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Ficha Documento']);
        $doc = $this->criarDocumentoSeed($m['id'], [
            'arquivo_original' => 'bo_ficha.pdf',
            'tipo'             => 'boletim_ocorrencia',
            'sigilo'           => 'confidencial',
            'observacoes'      => 'BO registrado na 123ª DP.',
        ]);
        $this->logarComo('admin');

        $result = $this->get('documentos/' . (int) $doc['id']);

        $result->assertStatus(200);
        $body = html_entity_decode($result->getBody(), ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('Documento #' . (int) $doc['id'], $body);
        $this->assertStringContainsString('bo_ficha.pdf', $body);
        $this->assertStringContainsString('Confidencial', $body);
        $this->assertStringContainsString('BO registrado na 123ª DP.', $body);
        $this->assertStringContainsString('Ficha Documento', $body);
        $this->assertStringContainsString('disponível', $body);
        $this->assertStringContainsString('Histórico', $body);
    }

    public function testVisualizarEntregaArquivoInline(): void
    {
        $m = $this->criarMulher();
        $doc = $this->criarDocumentoSeed($m['id'], ['arquivo_original' => 'vizualizacao.pdf']);
        $this->logarComo('admin');

        $result = $this->get('documentos/visualizar/' . (int) $doc['id']);

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'application/pdf');
        $result->assertHeader(
            'Content-Disposition',
            "inline; filename=\"vizualizacao.pdf\"; filename*=UTF-8''vizualizacao.pdf"
        );

        $historico = (new DocumentoHistoricoModel())
            ->where('documento_id', (int) $doc['id'])
            ->where('acao', 'download')
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($historico);
        $this->assertStringContainsString('Visualização', (string) $historico['observacao']);
    }

    public function testDownloadEnviaAnexoERegistraNoHistorico(): void
    {
        $m = $this->criarMulher();
        $doc = $this->criarDocumentoSeed($m['id'], ['arquivo_original' => 'download_teste.pdf']);
        $this->logarComo('admin');

        $result = $this->get('documentos/download/' . (int) $doc['id']);

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'application/pdf');
        $result->assertHeader(
            'Content-Disposition',
            "attachment; filename=\"download_teste.pdf\"; filename*=UTF-8''download_teste.pdf"
        );
        $this->assertStringContainsString(
            '%PDF',
            $result->getBody()
        );

        $historico = (new DocumentoHistoricoModel())
            ->where('documento_id', (int) $doc['id'])
            ->where('acao', 'download')
            ->first();
        $this->assertNotNull($historico);
        $this->assertStringContainsString('Download realizado.', (string) $historico['observacao']);
    }

    public function testEditarMantemMesmoRegistroSemMudarArquivo(): void
    {
        $m = $this->criarMulher();
        $doc = $this->criarDocumentoSeed($m['id'], ['arquivo_original' => 'edicao.pdf']);
        $this->logarComo('admin');

        $pagina = $this->get('documentos/editar/' . (int) $doc['id']);
        $pagina->assertStatus(200);
        $this->assertStringContainsString(
            'Editar Documento #' . (int) $doc['id'],
            html_entity_decode($pagina->getBody(), ENT_QUOTES, 'UTF-8')
        );

        $result = $this->post('documentos/atualizar/' . (int) $doc['id'], [
            'mulher_id'   => $m['id'],
            'tipo'        => 'relatorios',
            'sigilo'      => 'sigiloso',
            'observacoes' => 'Atualizado após reavaliação.',
        ]);

        $result->assertRedirectTo('/documentos/' . (int) $doc['id']);

        $atual = (new DocumentoModel())->find((int) $doc['id']);
        $this->assertNotNull($atual);
        $this->assertSame((int) $doc['id'], (int) $atual['id']);
        $this->assertSame('relatorios', $atual['tipo']);
        $this->assertSame('sigiloso', $atual['sigilo']);
        $this->assertSame($doc['arquivo'], $atual['arquivo']);
        $this->assertFileExists(WRITEPATH . 'uploads/' . $atual['arquivo']);
        $this->arquivosCriados[] = WRITEPATH . 'uploads/' . $atual['arquivo'];

        $historico = (new DocumentoHistoricoModel())
            ->where('documento_id', (int) $doc['id'])
            ->orderBy('id', 'DESC')
            ->findAll();
        $acoes = array_column($historico, 'acao');
        $this->assertContains('edicao', $acoes);
        $this->assertContains('sigilo', $acoes);

        $sigilo = null;
        foreach ($historico as $h) {
            if ($h['acao'] === 'sigilo') {
                $sigilo = $h;
                break;
            }
        }
        $this->assertNotNull($sigilo);
        $this->assertSame('restrito', $sigilo['status_anterior']);
        $this->assertSame('sigiloso', $sigilo['status_novo']);
    }

    public function testEditarPodeSubstituirArquivo(): void
    {
        $m = $this->criarMulher();
        $doc = $this->criarDocumentoSeed($m['id'], ['arquivo_original' => 'antigo.pdf']);
        $caminhoAntigo = WRITEPATH . 'uploads/' . $doc['arquivo'];
        $this->logarComo('admin');
        $this->anexarArquivo('novo_arquivo.pdf');

        $result = $this->post('documentos/atualizar/' . (int) $doc['id'], [
            'mulher_id' => $m['id'],
            'tipo'      => 'outros',
            'sigilo'    => 'restrito',
        ]);

        $result->assertRedirectTo('/documentos/' . (int) $doc['id']);

        $atual = (new DocumentoModel())->find((int) $doc['id']);
        $this->assertNotNull($atual);
        $this->assertNotSame($doc['arquivo'], $atual['arquivo']);
        $this->assertSame('novo_arquivo.pdf', $atual['arquivo_original']);
        $this->assertMatchesRegularExpression('#^documentos/\d{4}/\d{2}/[0-9a-f]{32}\.pdf$#', (string) $atual['arquivo']);
        $this->assertFileExists(WRITEPATH . 'uploads/' . $atual['arquivo']);
        $this->assertFileDoesNotExist($caminhoAntigo);
        $this->arquivosCriados[] = WRITEPATH . 'uploads/' . $atual['arquivo'];
    }

    public function testExcluirEhLogicoEPreservaArquivo(): void
    {
        $m = $this->criarMulher();
        $doc = $this->criarDocumentoSeed($m['id'], ['arquivo_original' => 'excluir.pdf']);
        $caminhoFisico = WRITEPATH . 'uploads/' . $doc['arquivo'];
        $this->logarComo('admin');

        $result = $this->post('documentos/' . (int) $doc['id'] . '/excluir');

        $result->assertRedirectTo('/documentos');
        $result->assertSessionHas('sucesso');

        $this->assertNull((new DocumentoModel())->find((int) $doc['id']));

        $oculto = (new DocumentoModel())->withDeleted()->find((int) $doc['id']);
        $this->assertNotNull($oculto);
        $this->assertNotNull($oculto['excluido_em']);
        $this->assertSame(1, (int) $oculto['excluido_por']);

        $this->assertFileExists($caminhoFisico);
        $this->arquivosCriados[] = $caminhoFisico;

        $historico = (new DocumentoHistoricoModel())
            ->where('documento_id', (int) $doc['id'])
            ->where('acao', 'exclusao')
            ->first();
        $this->assertNotNull($historico);
    }

    public function testDocumentoExcluidoNaoEntregaArquivo(): void
    {
        $m = $this->criarMulher();
        $doc = $this->criarDocumentoSeed($m['id']);
        $this->arquivosCriados[] = WRITEPATH . 'uploads/' . $doc['arquivo'];
        (new DocumentoModel())->update($doc['id'], [
            'excluido_em'  => date('Y-m-d H:i:s'),
            'excluido_por' => 1,
        ]);
        $this->logarComo('admin');

        $download = $this->get('documentos/download/' . (int) $doc['id']);
        $download->assertRedirectTo('/documentos');

        $visualizar = $this->get('documentos/visualizar/' . (int) $doc['id']);
        $visualizar->assertRedirectTo('/documentos');

        $detalhes = $this->get('documentos/' . (int) $doc['id']);
        $detalhes->assertRedirectTo('/documentos');
    }

    public function testBuscarProcessosRetornaJsonComMulher(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Proc Busca Doc']);
        $processo = $this->criarProcesso($m['id'], '0008888-88.2026.8.19.0014');
        $this->logarComo('admin');

        $result = $this->get('documentos/buscar-processos?term=0008888&mulher_id=' . $m['id']);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertNotEmpty($json['processos']);
        $this->assertSame((int) $processo['id'], (int) $json['processos'][0]['id']);
        $this->assertSame('Proc Busca Doc', $json['processos'][0]['mulher_nome']);

        $fora = $this->get('documentos/buscar-processos?term=0008888&mulher_id=999999');
        $fora->assertStatus(200);
        $jsonFora = json_decode($fora->getJSON(), true);
        $this->assertSame([], $jsonFora['processos']);
    }

    public function testPaginaNovaPreselecionaMulher(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Preselecao Documento']);
        $this->logarComo('admin');

        $result = $this->get('documentos/novo?mulher_id=' . $m['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Novo Documento', html_entity_decode($body, ENT_QUOTES, 'UTF-8'));
        $this->assertStringContainsString('Preselecao Documento', $body);
        $this->assertStringContainsString('value="' . $m['id'] . '"', $body);
    }

    public function testPaginaNovaPreselecionaProcessoEDerivaMulher(): void
    {
        $m = $this->criarMulher(['nome_completo' => 'Presel Proc Doc']);
        $processo = $this->criarProcesso($m['id'], '0005555-55.2026.8.19.0014');
        $this->logarComo('admin');

        $result = $this->get('documentos/novo?processo_id=' . (int) $processo['id']);

        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('Presel Proc Doc', $body);
        $this->assertStringContainsString('value="' . $m['id'] . '"', $body);
        $this->assertStringContainsString('value="' . (int) $processo['id'] . '"', $body);
    }
}